<?php

namespace App\Imports;

use App\Support\IpAssigner;
use App\Support\KardexMovimiento;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EquiposImporter
{
    // Header names that map to editable DB fields
    private const CAMPO_MAP = [
        'No. Inventario'  => 'num_inventario',
        'Nombre Equipo'   => 'nombre_equipo',
        'Área'            => 'area',
        'Marca CPU'       => 'cpu_marca',
        'Modelo CPU'      => 'cpu_modelo',
        'Teclado S/N'     => 'teclado_serie',
        'Mouse S/N'       => 'mouse_serie',
        'Monitor Marca'   => 'monitor_marca',
        'Monitor Modelo'  => 'monitor_modelo',
        'Monitor S/N'     => 'monitor_serie',
        'No-Break Marca'  => 'nobreak_marca',
        'No-Break Modelo' => 'nobreak_modelo',
        'No-Break S/N'    => 'nobreak_serie',
        'Cargador S/N'    => 'cargador_serie',
        'Docking Marca'   => 'docking_marca',
        'Docking Modelo'  => 'docking_modelo',
        'Docking S/N'     => 'docking_serie',
        'Candado'         => 'candado',
        'MAC'             => 'mac',
        'Observaciones'   => 'observaciones',
    ];

    public function process(string $filePath, bool $dryRun = true): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName('EQUIPOS') ?? $spreadsheet->getActiveSheet();

        // Build header → column letter map from row 1
        $colMap = [];
        foreach ($sheet->getRowIterator(1, 1) as $row) {
            foreach ($row->getCellIterator('A', $sheet->getHighestDataColumn()) as $cell) {
                $header = trim((string) $cell->getValue());
                if ($header !== '') {
                    $colMap[$header] = $cell->getColumn();
                }
            }
        }

        $serieCol = $colMap['No. Serie'] ?? null;
        if (!$serieCol) {
            throw new \RuntimeException('Columna "No. Serie" no encontrada. Asegúrate de usar el formato descargado desde el sistema.');
        }

        // Valid areas (case-insensitive)
        $areasValidas = DB::table('cat_rangos_ips')
            ->pluck('area_nombre')
            ->map(fn($a) => mb_strtolower(trim($a)))
            ->toArray();

        $resultado = [
            'total'         => 0,
            'actualizables' => 0,
            'sin_cambios'   => 0,
            'errores'       => 0,
            'detalle'       => [],
        ];

        $highestRow = $sheet->getHighestDataRow();

        for ($rowNum = 2; $rowNum <= $highestRow; $rowNum++) {
            $serie = trim((string) $sheet->getCell($serieCol . $rowNum)->getValue());
            if ($serie === '' || $serie === '—') {
                continue;
            }

            $resultado['total']++;

            $eq = DB::table('inventario_equipos')
                ->leftJoin('users', 'inventario_equipos.user_id', '=', 'users.id')
                ->select(
                    'inventario_equipos.*',
                    DB::raw("NULLIF(TRIM(COALESCE(users.name,'') || ' ' || COALESCE(users.apellido_paterno,'')), '') as empleado_nombre")
                )
                ->where('inventario_equipos.cpu_serie', $serie)
                ->first();

            if (!$eq) {
                $resultado['errores']++;
                $resultado['detalle'][] = [
                    'fila'   => $rowNum,
                    'serie'  => $serie,
                    'estado' => 'error',
                    'razon'  => 'Serie no encontrada en el sistema.',
                ];
                continue;
            }

            if ($eq->pdf_resguardo || $eq->user_id) {
                $resultado['errores']++;
                $resultado['detalle'][] = [
                    'fila'   => $rowNum,
                    'serie'  => $serie,
                    'estado' => 'error',
                    'razon'  => 'Este equipo tiene resguardo activo. Para modificar sus datos, introduce un nuevo PDF de resguardo desde el módulo Kardex — no se puede hacer por importación masiva.',
                ];
                continue;
            }

            $updates  = [];
            $cambios  = [];
            $errFila  = [];

            foreach (self::CAMPO_MAP as $header => $dbField) {
                $col = $colMap[$header] ?? null;
                if (!$col) {
                    continue;
                }

                $val     = trim((string) $sheet->getCell($col . $rowNum)->getValue());
                $current = trim((string) ($eq->$dbField ?? ''));

                // Empty cell or unchanged value → keep current, nothing to do
                if ($val === '' || $val === '—' || $val === $current) {
                    continue;
                }

                // Field-specific validation (only runs when value is actually changing)
                if ($dbField === 'area') {
                    $match = in_array(
                        mb_strtolower($val),
                        array_map('mb_strtolower', $areasValidas)
                    );
                    if (!$match) {
                        $errFila[] = "Área \"{$val}\" no está en el catálogo. Usa el desplegable del formato.";
                        continue;
                    }
                }

                if ($dbField === 'mac') {
                    if (!preg_match('/^([0-9A-Fa-f]{2}[:\-]){5}[0-9A-Fa-f]{2}$/', $val)) {
                        $errFila[] = "MAC \"{$val}\" tiene formato incorrecto (esperado: XX:XX:XX:XX:XX:XX).";
                        continue;
                    }
                }

                $updates[$dbField] = $val;
                $label = $current === '' ? '(vacío)' : $current;
                $cambios[] = "{$header}: {$label} → {$val}";
            }

            // ── Responsable (resuelve nombre → user_id si hay match, texto si no) ─
            $respCol = $colMap['Responsable'] ?? null;
            if ($respCol) {
                $respVal     = trim((string) $sheet->getCell($respCol . $rowNum)->getValue());
                $currentResp = trim((string) ($eq->nombre_usuario ?? $eq->empleado_nombre ?? ''));
                if ($respVal !== '' && $respVal !== '—' && mb_strtolower($respVal) !== mb_strtolower($currentResp)) {
                    // Compare in PHP to avoid SQLite LOWER() not handling accents
                    $allUsers = DB::table('users')->where('activo', 1)
                        ->get(['id', 'name', 'apellido_paterno']);
                    $matchedUser = $allUsers->first(function ($u) use ($respVal) {
                        $full = trim(($u->name ?? '') . ' ' . ($u->apellido_paterno ?? ''));
                        return mb_strtolower($full) === mb_strtolower($respVal);
                    });

                    if ($matchedUser) {
                        $updates['user_id']        = $matchedUser->id;
                        $updates['nombre_usuario'] = $respVal;
                        $cambios[] = "Responsable: {$currentResp} → {$respVal} (usuario vinculado)";
                    } else {
                        // No exact match → store as legacy text, don't block the row
                        $updates['nombre_usuario'] = $respVal;
                        $cambios[] = "Responsable (texto): {$currentResp} → {$respVal}";
                    }
                }
            }

            // ── IP (resuelve IP → ip_id usando IpAssigner) ────────────────────
            $ipCol = $colMap['IP'] ?? null;
            if ($ipCol) {
                $ipVal     = trim((string) $sheet->getCell($ipCol . $rowNum)->getValue());
                $currentIp = trim((string) ($eq->ipv4 ?? ''));
                if ($ipVal !== '' && $ipVal !== '—' && $ipVal !== $currentIp) {
                    $ipRow = DB::table('inventario_ips_completo')->where('ip', $ipVal)->first();
                    if (!$ipRow) {
                        $errFila[] = "IP \"{$ipVal}\" no está registrada en el sistema.";
                    } else {
                        // Check if IP belongs to a resguarded device
                        $lockedByResguardo = DB::table('inventario_equipos')
                            ->where('ip_id', $ipRow->id)
                            ->where(function ($q) {
                                $q->whereNotNull('pdf_resguardo')->orWhereNotNull('user_id');
                            })
                            ->exists();
                        if ($lockedByResguardo) {
                            $errFila[] = "IP \"{$ipVal}\" está asignada a un equipo con resguardo activo.";
                        } elseif (IpAssigner::assignedElsewhere($ipRow->id, 'inventario_equipos', $eq->id)) {
                            $errFila[] = "IP \"{$ipVal}\" ya está en uso por otro equipo.";
                        } else {
                            if (!$dryRun) {
                                // Release old IP if any
                                if ($eq->ip_id) {
                                    DB::table('inventario_ips_completo')
                                        ->where('id', $eq->ip_id)
                                        ->update(['estatus' => 'Libre', 'updated_at' => now()]);
                                    DB::table('inventario_equipos')
                                        ->where('id', $eq->id)
                                        ->update(['ip_id' => null, 'ipv4' => null]);
                                }
                                $updates['ip_id'] = IpAssigner::resolveId($ipVal);
                            }
                            $updates['ipv4'] = $ipVal;
                            $cambios[] = "IP: {$currentIp} → {$ipVal}";
                        }
                    }
                }
            }

            if (!empty($errFila)) {
                $resultado['errores']++;
                $resultado['detalle'][] = [
                    'fila'   => $rowNum,
                    'serie'  => $serie,
                    'estado' => 'error',
                    'razon'  => implode(' | ', $errFila),
                ];
                continue;
            }

            if (empty($updates)) {
                $resultado['sin_cambios']++;
                $resultado['detalle'][] = [
                    'fila'   => $rowNum,
                    'serie'  => $serie,
                    'estado' => 'sin_cambios',
                    'razon'  => 'Sin cambios detectados.',
                ];
                continue;
            }

            if (!$dryRun) {
                $updates['updated_at'] = now();
                DB::table('inventario_equipos')->where('id', $eq->id)->update($updates);

                KardexMovimiento::registrar(
                    tipo_activo:   'equipo',
                    activo_id:     $eq->id,
                    tipo_evento:   'Actualización Masiva',
                    origen:        'Importación Excel',
                    destino:       'Importación Excel',
                    estado_equipo: null,
                    notas:         implode(' | ', $cambios),
                );
            }

            $resultado['actualizables']++;
            $resultado['detalle'][] = [
                'fila'    => $rowNum,
                'serie'   => $serie,
                'estado'  => 'ok',
                'cambios' => $cambios,
            ];
        }

        return $resultado;
    }
}
