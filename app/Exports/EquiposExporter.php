<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EquiposExporter extends BaseExporter
{
    // Columns for the importable format (not the same as the regular CSV export)
    private const FORMATO_HEADERS = [
        'A' => ['header' => 'No. Inventario', 'field' => 'num_inventario',  'editable' => true],
        'B' => ['header' => 'Nombre Equipo',  'field' => 'nombre_equipo',   'editable' => true],
        'C' => ['header' => 'Tipo',           'field' => 'tipo',            'editable' => false],
        'D' => ['header' => 'No. Serie',      'field' => 'cpu_serie',       'editable' => false, 'key' => true],
        'E' => ['header' => 'Área',           'field' => 'area',            'editable' => true,  'dropdown' => 'area'],
        'F' => ['header' => 'Responsable',    'field' => '_responsable',    'editable' => true,  'dropdown' => 'responsable'],
        'G' => ['header' => 'Estado',         'field' => '_estado',         'editable' => false],
        'H' => ['header' => 'Marca CPU',      'field' => 'cpu_marca',       'editable' => true],
        'I' => ['header' => 'Modelo CPU',     'field' => 'cpu_modelo',      'editable' => true],
        'J' => ['header' => 'Teclado S/N',    'field' => 'teclado_serie',   'editable' => true],
        'K' => ['header' => 'Mouse S/N',      'field' => 'mouse_serie',     'editable' => true],
        'L' => ['header' => 'Monitor Marca',  'field' => 'monitor_marca',   'editable' => true],
        'M' => ['header' => 'Monitor Modelo', 'field' => 'monitor_modelo',  'editable' => true],
        'N' => ['header' => 'Monitor S/N',    'field' => 'monitor_serie',   'editable' => true],
        'O' => ['header' => 'No-Break Marca', 'field' => 'nobreak_marca',   'editable' => true],
        'P' => ['header' => 'No-Break Modelo','field' => 'nobreak_modelo',  'editable' => true],
        'Q' => ['header' => 'No-Break S/N',   'field' => 'nobreak_serie',   'editable' => true],
        'R' => ['header' => 'Cargador S/N',   'field' => 'cargador_serie',  'editable' => true],
        'S' => ['header' => 'Docking Marca',  'field' => 'docking_marca',   'editable' => true],
        'T' => ['header' => 'Docking Modelo', 'field' => 'docking_modelo',  'editable' => true],
        'U' => ['header' => 'Docking S/N',    'field' => 'docking_serie',   'editable' => true],
        'V' => ['header' => 'Candado',        'field' => 'candado',         'editable' => true],
        'W' => ['header' => 'MAC',            'field' => 'mac',             'editable' => true],
        'X' => ['header' => 'IP',             'field' => 'ipv4',            'editable' => true,  'dropdown' => 'ip'],
        'Y' => ['header' => 'Observaciones',  'field' => 'observaciones',   'editable' => true],
    ];

    public function download(array $filters): StreamedResponse
    {
        if (!empty($filters['solo_encabezados'])) {
            return $this->downloadFormato($filters);
        }
        return parent::download($filters);
    }

    private function downloadFormato(array $filters): StreamedResponse
    {
        // Fetch equipment WITHOUT resguardo, applying active filters
        $query = DB::table('inventario_equipos')
            ->leftJoin('users', 'inventario_equipos.user_id', '=', 'users.id')
            ->select(
                'inventario_equipos.*',
                DB::raw("NULLIF(TRIM(COALESCE(users.name,'') || ' ' || COALESCE(users.apellido_paterno,'')), '') as empleado_nombre")
            )
            ->where(function ($q) {
                $q->whereNull('inventario_equipos.pdf_resguardo')
                  ->whereNull('inventario_equipos.user_id');
            })
            ->orderBy('inventario_equipos.tipo')
            ->orderBy('inventario_equipos.consecutivo');

        if (!empty($filters['f-eq-tipo'])) {
            $query->where('inventario_equipos.tipo', $filters['f-eq-tipo']);
        }
        if (!empty($filters['f-eq-texto'])) {
            $q = $filters['f-eq-texto'];
            $query->where(function ($w) use ($q) {
                $w->where(DB::raw('LOWER(inventario_equipos.cpu_serie)'), 'like', '%' . strtolower($q) . '%')
                  ->orWhere(DB::raw('LOWER(inventario_equipos.area)'), 'like', '%' . strtolower($q) . '%');
            });
        }

        // Count excluded (have resguardo but match other filters)
        $excluidos = DB::table('inventario_equipos')
            ->where(function ($q) {
                $q->whereNotNull('inventario_equipos.pdf_resguardo')
                  ->orWhereNotNull('inventario_equipos.user_id');
            });
        if (!empty($filters['f-eq-tipo'])) {
            $excluidos->where('tipo', $filters['f-eq-tipo']);
        }
        $totalExcluidos = $excluidos->count();

        $equipos = $query->get();

        // Valid areas for CATALOGOS sheet
        $areas = DB::table('cat_rangos_ips')
            ->orderBy('area_nombre')
            ->pluck('area_nombre')
            ->unique()
            ->values()
            ->toArray();

        // Active users for Responsable dropdown
        $usuarios = DB::table('users')
            ->where('activo', 1)
            ->orderBy('name')
            ->orderBy('apellido_paterno')
            ->selectRaw("NULLIF(TRIM(COALESCE(name,'') || ' ' || COALESCE(apellido_paterno,'')), '') as nombre_completo")
            ->pluck('nombre_completo')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // IPs NOT locked by a resguarded equipment
        $ipsDisponibles = DB::table('inventario_ips_completo')
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('inventario_equipos')
                    ->whereColumn('inventario_equipos.ip_id', 'inventario_ips_completo.id')
                    ->where(function ($q) {
                        $q->whereNotNull('inventario_equipos.pdf_resguardo')
                          ->orWhereNotNull('inventario_equipos.user_id');
                    });
            })
            ->orderByRaw("CAST(REPLACE(ip, '.', '') AS INTEGER)")
            ->pluck('ip')
            ->toArray();

        $spreadsheet = new Spreadsheet();

        // ── Sheet 1: INSTRUCCIONES ────────────────────────────────────────────
        $shInstr = $spreadsheet->getActiveSheet()->setTitle('INSTRUCCIONES');
        $this->buildInstrSheet($shInstr, $totalExcluidos, $filters);

        // ── Sheet 2: EQUIPOS ──────────────────────────────────────────────────
        $shEquipos = $spreadsheet->createSheet()->setTitle('EQUIPOS');
        $this->buildEquiposSheet($shEquipos, $equipos, $areas, $usuarios, $ipsDisponibles);

        // ── Sheet 3: CATALOGOS (hidden) ───────────────────────────────────────
        $shCat = $spreadsheet->createSheet()->setTitle('CATALOGOS');
        $shCat->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
        $this->buildCatalogosSheet($shCat, $areas, $usuarios, $ipsDisponibles);

        // Set active sheet to EQUIPOS on open
        $spreadsheet->setActiveSheetIndexByName('EQUIPOS');

        $filename = 'formato_equipos_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new XlsxWriter($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function buildInstrSheet(Worksheet $sh, int $excluidos, array $filters): void
    {
        $sh->getColumnDimension('A')->setWidth(28);
        $sh->getColumnDimension('B')->setWidth(14);
        $sh->getColumnDimension('C')->setWidth(30);
        $sh->getColumnDimension('D')->setWidth(30);

        $row = 1;

        // Title
        $sh->setCellValue("A{$row}", '📋 Instrucciones de importación — Inventario de Equipos');
        $sh->getStyle("A{$row}")->getFont()->setBold(true)->setSize(13);
        $sh->mergeCells("A{$row}:D{$row}");
        $row += 2;

        // Warning if there are excluded resguardos
        if ($excluidos > 0) {
            $sh->setCellValue("A{$row}", "ℹ️ Se excluyeron {$excluidos} equipo(s) con resguardo activo. Para modificar esos equipos, introduce un nuevo PDF de resguardo desde el módulo Kardex.");
            $sh->mergeCells("A{$row}:D{$row}");
            $sh->getStyle("A{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FEF3C7']],
                'font' => ['color' => ['rgb' => '92400E']],
                'alignment' => ['wrapText' => true],
            ]);
            $sh->getRowDimension($row)->setRowHeight(36);
            $row += 2;
        }

        // Rules
        $sh->setCellValue("A{$row}", '⚠️ Reglas importantes');
        $sh->getStyle("A{$row}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('C0392B'));
        $sh->mergeCells("A{$row}:D{$row}");
        $row++;

        foreach ([
            'NO modifiques la columna "No. Serie" — es el identificador de cada equipo.',
            'NO agregues ni elimines filas.',
            'Las celdas en gris están bloqueadas y no se importarán aunque las modifiques.',
            'Las celdas blancas son editables. Si dejas una celda vacía, se conserva el valor actual.',
            'El campo Área debe seleccionarse del desplegable — valores fuera del catálogo serán rechazados.',
        ] as $regla) {
            $sh->setCellValue("A{$row}", "• {$regla}");
            $sh->mergeCells("A{$row}:D{$row}");
            $row++;
        }

        $row++;

        // Column guide header
        foreach (['Columna', 'Editable', 'Valores válidos / Notas', 'Ejemplo'] as $i => $h) {
            $col = chr(65 + $i);
            $sh->setCellValue("{$col}{$row}", $h);
            $sh->getStyle("{$col}{$row}")->getFont()->setBold(true);
            $sh->getStyle("{$col}{$row}")->getFill()
               ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1A3A5C');
            $sh->getStyle("{$col}{$row}")->getFont()->getColor()->setRGB('FFFFFF');
        }
        $row++;

        $guide = [
            ['No. Inventario',  'Sí',  'Texto libre',                      'EQ-2024-001'],
            ['Nombre Equipo',   'Sí',  'Texto libre',                      'LAPTOP-DIR-01'],
            ['Tipo',            'No',  'Solo lectura',                     '—'],
            ['No. Serie',       'No',  'Identificador — NO modificar',     'ABC123XYZ'],
            ['Área',            'Sí',  'Desplegable del catálogo',         'Subdirección de Sistemas'],
            ['Responsable',     'No',  'Solo lectura',                     '—'],
            ['Estado',          'No',  'Solo lectura',                     '—'],
            ['Marca CPU',       'Sí',  'Texto libre',                      'Dell'],
            ['Modelo CPU',      'Sí',  'Texto libre',                      'Latitude 5420'],
            ['Teclado S/N',     'Sí',  'Texto libre',                      'KB123'],
            ['Mouse S/N',       'Sí',  'Texto libre',                      'MS456'],
            ['Monitor Marca',   'Sí',  'Texto libre',                      'LG'],
            ['Monitor Modelo',  'Sí',  'Texto libre',                      '24MP400'],
            ['Monitor S/N',     'Sí',  'Texto libre',                      'MN789'],
            ['No-Break Marca',  'Sí',  'Texto libre',                      'APC'],
            ['No-Break Modelo', 'Sí',  'Texto libre',                      'BE600M1'],
            ['No-Break S/N',    'Sí',  'Texto libre',                      'NB001'],
            ['Cargador S/N',    'Sí',  'Texto libre (solo Laptops)',        'CRG22'],
            ['Docking Marca',   'Sí',  'Texto libre',                      'Dell'],
            ['Docking Modelo',  'Sí',  'Texto libre',                      'WD19S'],
            ['Docking S/N',     'Sí',  'Texto libre',                      'DK55'],
            ['Candado',         'Sí',  'Texto libre',                      'Kensington K64'],
            ['MAC',             'Sí',  'Formato: XX:XX:XX:XX:XX:XX',       'AA:BB:CC:DD:EE:FF'],
            ['IP',              'No',  'Solo lectura — gestionada por Network', '—'],
            ['Observaciones',   'Sí',  'Texto libre',                      'Batería dañada'],
        ];

        foreach ($guide as $g) {
            $sh->setCellValue("A{$row}", $g[0]);
            $sh->setCellValue("B{$row}", $g[1]);
            $sh->setCellValue("C{$row}", $g[2]);
            $sh->setCellValue("D{$row}", $g[3]);
            if ($g[1] === 'No') {
                $sh->getStyle("A{$row}:D{$row}")->getFill()
                   ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F0F0');
                $sh->getStyle("B{$row}")->getFont()->getColor()->setRGB('888888');
            }
            $row++;
        }
    }

    private function buildEquiposSheet(Worksheet $sh, $equipos, array $areas, array $usuarios, array $ips): void
    {
        $lastCol  = 'Y';
        $dataRows = count($equipos);
        $lastRow  = max($dataRows + 1, 2);

        // ── Column widths ─────────────────────────────────────────────────────
        $widths = [
            'A'=>16,'B'=>18,'C'=>16,'D'=>20,'E'=>26,'F'=>24,'G'=>14,
            'H'=>14,'I'=>20,'J'=>14,'K'=>14,'L'=>14,'M'=>18,'N'=>14,
            'O'=>16,'P'=>18,'Q'=>14,'R'=>14,'S'=>14,'T'=>18,'U'=>14,
            'V'=>14,'W'=>20,'X'=>16,'Y'=>28,
        ];
        foreach ($widths as $col => $w) {
            $sh->getColumnDimension($col)->setWidth($w);
        }

        // ── Header row ────────────────────────────────────────────────────────
        $lockedHdrColor  = '9E9E9E';  // grey for locked columns
        $editableHdrColor = '1A3A5C'; // dark blue for editable columns
        $keyHdrColor     = '8B4513';  // brown for identifier column

        foreach (self::FORMATO_HEADERS as $col => $def) {
            $sh->setCellValue("{$col}1", $def['header']);
            $bgColor = ($def['key'] ?? false) ? $keyHdrColor : ($def['editable'] ? $editableHdrColor : $lockedHdrColor);
            $sh->getStyle("{$col}1")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $bgColor]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
        }
        $sh->getRowDimension(1)->setRowHeight(28);

        // ── Data rows ─────────────────────────────────────────────────────────
        foreach ($equipos as $i => $eq) {
            $row = $i + 2;
            $responsable = $eq->empleado_nombre ?: ($eq->nombre_usuario ?? '');
            $estado = match (true) {
                $eq->estado === 'mantenimiento' => 'Mantenimiento',
                $eq->estado === 'baja'          => 'Baja',
                !is_null($eq->user_id)          => 'Asignado',
                default                         => 'Almacén',
            };

            $values = [
                'A' => $eq->num_inventario ?? '',
                'B' => $eq->nombre_equipo  ?? '',
                'C' => $eq->tipo           ?? '',
                'D' => $eq->cpu_serie      ?? '',
                'E' => $eq->area           ?? '',
                'F' => $responsable,
                'G' => $estado,
                'H' => $eq->cpu_marca      ?? '',
                'I' => $eq->cpu_modelo     ?? '',
                'J' => $eq->teclado_serie  ?? '',
                'K' => $eq->mouse_serie    ?? '',
                'L' => $eq->monitor_marca  ?? '',
                'M' => $eq->monitor_modelo ?? '',
                'N' => $eq->monitor_serie  ?? '',
                'O' => $eq->nobreak_marca  ?? '',
                'P' => $eq->nobreak_modelo ?? '',
                'Q' => $eq->nobreak_serie  ?? '',
                'R' => $eq->cargador_serie ?? '',
                'S' => $eq->docking_marca  ?? '',
                'T' => $eq->docking_modelo ?? '',
                'U' => $eq->docking_serie  ?? '',
                'V' => $eq->candado        ?? '',
                'W' => $eq->mac            ?? '',
                'X' => $eq->ipv4           ?? '',
                'Y' => $eq->observaciones  ?? '',
            ];

            foreach ($values as $col => $val) {
                $sh->setCellValue("{$col}{$row}", $val);
            }

            // Row zebra
            if ($i % 2 === 1) {
                $sh->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                   ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FAFAFA');
            }
        }

        // ── Apply styles: locked (grey) vs editable (white) ──────────────────
        if ($lastRow > 1) {
            foreach (self::FORMATO_HEADERS as $col => $def) {
                $range = "{$col}2:{$col}{$lastRow}";
                if ($def['editable']) {
                    // White, unlocked
                    $sh->getStyle($range)->getFill()
                       ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
                    $sh->getStyle($range)->getProtection()
                       ->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);
                } else {
                    // Grey, locked
                    $bgRgb = ($def['key'] ?? false) ? 'FFE8D6' : 'EEEEEE';
                    $sh->getStyle($range)->getFill()
                       ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bgRgb);
                    $sh->getStyle($range)->getProtection()
                       ->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_PROTECTED);
                }
            }
        }

        // ── Data validation dropdowns ─────────────────────────────────────────
        $dropdowns = [
            'E' => ['values' => $areas,    'lastRow' => count($areas) + 1,    'catCol' => 'A', 'errTitle' => 'Área inválida',       'errMsg' => 'Selecciona un área del catálogo.'],
            'F' => ['values' => $usuarios, 'lastRow' => count($usuarios) + 1, 'catCol' => 'C', 'errTitle' => 'Responsable inválido','errMsg' => 'Selecciona un responsable del listado.'],
            'X' => ['values' => $ips,      'lastRow' => count($ips) + 1,      'catCol' => 'D', 'errTitle' => 'IP inválida',         'errMsg' => 'Selecciona una IP disponible del listado.'],
        ];

        foreach ($dropdowns as $col => $cfg) {
            if (empty($cfg['values']) || $lastRow <= 1) continue;
            for ($row = 2; $row <= $lastRow; $row++) {
                $v = $sh->getCell("{$col}{$row}")->getDataValidation();
                $v->setType(DataValidation::TYPE_LIST);
                $v->setErrorStyle(DataValidation::STYLE_STOP);
                $v->setAllowBlank(true);
                $v->setShowDropDown(false);
                $v->setShowErrorMessage(true);
                $v->setErrorTitle($cfg['errTitle']);
                $v->setError($cfg['errMsg']);
                $v->setFormula1("CATALOGOS!\${$cfg['catCol']}\$2:\${$cfg['catCol']}\${$cfg['lastRow']}");
            }
        }

        // ── Freeze panes + protect sheet ──────────────────────────────────────
        $sh->freezePane('A2');
        $sh->getProtection()->setSheet(true)->setPassword('');

        // Auto filter on header row
        $sh->setAutoFilter("A1:{$lastCol}1");
    }

    private function buildCatalogosSheet(Worksheet $sh, array $areas, array $usuarios, array $ips): void
    {
        foreach (['A' => 'AREAS', 'B' => 'TIPOS', 'C' => 'RESPONSABLES', 'D' => 'IPS'] as $col => $titulo) {
            $sh->setCellValue("{$col}1", $titulo);
            $sh->getStyle("{$col}1")->getFont()->setBold(true);
        }

        foreach ($areas as $i => $area) {
            $sh->setCellValue('A' . ($i + 2), $area);
        }

        foreach (['Laptop', 'PC Avanzada', 'PC Especializada'] as $i => $tipo) {
            $sh->setCellValue('B' . ($i + 2), $tipo);
        }

        foreach ($usuarios as $i => $nombre) {
            $sh->setCellValue('C' . ($i + 2), $nombre);
        }

        foreach ($ips as $i => $ip) {
            $sh->setCellValue('D' . ($i + 2), $ip);
        }
    }
    public function headers(): array
    {
        return [
            'No. Inventario', 'Tipo', 'Marca', 'Modelo', 'No. Serie',
            'Área', 'Responsable', 'Estado',
            'Teclado S/N', 'Mouse S/N',
            'Monitor Marca', 'Monitor Modelo', 'Monitor S/N',
            'No-Break Marca', 'No-Break Modelo', 'No-Break S/N',
            'Cargador S/N',
            'Docking Marca', 'Docking Modelo', 'Docking S/N',
            'Candado', 'IP', 'MAC',
        ];
    }

    public function rows(array $filters): array
    {
        $query = DB::table('inventario_equipos')
            ->leftJoin('users', 'inventario_equipos.user_id', '=', 'users.id')
            ->select(
                'inventario_equipos.*',
                DB::raw("NULLIF(TRIM(COALESCE(users.name,'') || ' ' || COALESCE(users.apellido_paterno,'')), '') as empleado_nombre")
            )
            ->orderBy('inventario_equipos.tipo')
            ->orderBy('inventario_equipos.consecutivo');

        if (!empty($filters['f-eq-tipo'])) {
            $query->where('inventario_equipos.tipo', $filters['f-eq-tipo']);
        }

        if (!empty($filters['f-eq-estado'])) {
            $this->aplicarFiltroEstado($query, $filters['f-eq-estado']);
        }

        if (!empty($filters['f-eq-texto'])) {
            $q = $filters['f-eq-texto'];
            $query->where(function ($w) use ($q) {
                $w->where(DB::raw('LOWER(inventario_equipos.area)'), 'like', '%' . strtolower($q) . '%')
                  ->orWhere(DB::raw('LOWER(inventario_equipos.nombre_usuario)'), 'like', '%' . strtolower($q) . '%')
                  ->orWhere(DB::raw('LOWER(inventario_equipos.cpu_serie)'), 'like', '%' . strtolower($q) . '%')
                  ->orWhere(DB::raw('LOWER(users.name)'), 'like', '%' . strtolower($q) . '%');
            });
        }

        $rows = [];
        foreach ($query->get() as $eq) {
            $responsable    = $eq->empleado_nombre ?: ($eq->nombre_usuario ?? '—');
            $estadoDisplay  = $this->estadoDisplay($eq);
            $ip             = $eq->ipv4 ?: ($eq->ipv4_actual ?? '');

            $rows[] = [
                $eq->num_inventario ?? '—',
                $eq->tipo ?? '—',
                $eq->cpu_marca ?? '—',
                $eq->cpu_modelo ?? '—',
                $eq->cpu_serie ?? '—',
                $eq->area ?? '—',
                $responsable,
                $estadoDisplay,
                $eq->teclado_serie ?? '',
                $eq->mouse_serie ?? '',
                $eq->monitor_marca ?? '',
                $eq->monitor_modelo ?? '',
                $eq->monitor_serie ?? '',
                $eq->nobreak_marca ?? '',
                $eq->nobreak_modelo ?? '',
                $eq->nobreak_serie ?? '',
                $eq->cargador_serie ?? '',
                $eq->docking_marca ?? '',
                $eq->docking_modelo ?? '',
                $eq->docking_serie ?? '',
                $eq->candado ?? '',
                $ip,
                $eq->mac ?? '',
            ];
        }

        return $rows;
    }

    private function aplicarFiltroEstado($query, string $estado): void
    {
        match ($estado) {
            'Almacén'      => $query->whereNull('inventario_equipos.user_id')
                                    ->whereNotIn('inventario_equipos.estado', ['mantenimiento', 'baja'])
                                    ->orWhereNull('inventario_equipos.estado'),
            'Asignado'     => $query->whereNotNull('inventario_equipos.user_id'),
            'Mantenimiento'=> $query->where('inventario_equipos.estado', 'mantenimiento'),
            'Baja'         => $query->where('inventario_equipos.estado', 'baja'),
            default        => null,
        };
    }

    private function estadoDisplay(object $eq): string
    {
        return match (true) {
            $eq->estado === 'mantenimiento' => 'Mantenimiento',
            $eq->estado === 'baja'          => 'Baja',
            !is_null($eq->user_id)          => 'Asignado',
            default                         => 'Almacén',
        };
    }

    public function filename(): string
    {
        return 'kardex_equipos';
    }
}
