<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ResguardosExporter extends BaseExporter
{
    // headers/rows/filename son para el TXT de fallback
    public function headers(): array
    {
        return ['ID', 'Tipo', 'Marca', 'Modelo', 'No. Serie', 'No. Inventario', 'Área', 'Responsable', 'Estado'];
    }

    public function rows(array $filters): array
    {
        return []; // No se usa directamente; se usa getEquipos() en download()
    }

    public function filename(): string
    {
        return 'resguardos';
    }

    /** Genera un ZIP con PDFs (si existen) o TXTs por cada equipo */
    public function download(array $filters): Response
    {
        $equipos = $this->getEquipos($filters);

        $tmpPath = tempnam(sys_get_temp_dir(), 'imjrsg') . '.zip';
        $zip     = new \ZipArchive();

        if ($zip->open($tmpPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'No se pudo crear el archivo ZIP.');
        }

        foreach ($equipos as $eq) {
            $safeSerie = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $eq->cpu_serie ?? ('id' . $eq->id));
            $nombre    = "equipo_{$eq->id}_{$safeSerie}";

            if ($eq->pdf_resguardo && Storage::disk('local')->exists($eq->pdf_resguardo)) {
                $zip->addFromString("{$nombre}.pdf", Storage::disk('local')->get($eq->pdf_resguardo));
            } else {
                $zip->addFromString("{$nombre}.txt", $this->buildTxt($eq));
            }
        }

        $zip->close();

        $downloadName = 'resguardos_' . now()->format('Ymd_His') . '.zip';

        return response()->download($tmpPath, $downloadName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    private function getEquipos(array $filters): \Illuminate\Support\Collection
    {
        $query = DB::table('inventario_equipos')
            ->leftJoin('users', 'inventario_equipos.user_id', '=', 'users.id')
            ->select(
                'inventario_equipos.*',
                DB::raw("NULLIF(TRIM(COALESCE(users.name,'') || ' ' || COALESCE(users.apellido_paterno,'')), '') as empleado_nombre")
            )
            ->orderBy('inventario_equipos.tipo')
            ->orderBy('inventario_equipos.consecutivo');

        if (!empty($filters['f-rsg-tipo'])) {
            $query->where('inventario_equipos.tipo', $filters['f-rsg-tipo']);
        }

        if (!empty($filters['f-rsg-pdf'])) {
            if ($filters['f-rsg-pdf'] === 'si') {
                $query->whereNotNull('inventario_equipos.pdf_resguardo');
            } elseif ($filters['f-rsg-pdf'] === 'no') {
                $query->whereNull('inventario_equipos.pdf_resguardo');
            }
        }

        if (!empty($filters['f-rsg-texto'])) {
            $q = $filters['f-rsg-texto'];
            $query->where(function ($w) use ($q) {
                $w->where(DB::raw('LOWER(inventario_equipos.area)'), 'like', '%' . strtolower($q) . '%')
                  ->orWhere(DB::raw('LOWER(inventario_equipos.nombre_usuario)'), 'like', '%' . strtolower($q) . '%')
                  ->orWhere(DB::raw('LOWER(users.name)'), 'like', '%' . strtolower($q) . '%');
            });
        }

        return $query->get();
    }

    private function buildTxt(object $eq): string
    {
        $responsable = $eq->empleado_nombre ?: ($eq->nombre_usuario ?? '—');
        $estado      = match (true) {
            $eq->estado === 'mantenimiento' => 'Mantenimiento',
            $eq->estado === 'baja'          => 'Baja',
            !is_null($eq->user_id)          => 'Asignado',
            default                         => 'Almacén',
        };

        $sep = str_repeat('─', 44);

        $lines = [
            'RESGUARDO DE EQUIPO — IMJUVE',
            str_repeat('═', 44),
            '',
            sprintf("%-18s %s", 'ID:', '#' . $eq->id),
            sprintf("%-18s %s", 'No. Inventario:', $eq->num_inventario ?? '—'),
            sprintf("%-18s %s", 'Tipo:', $eq->tipo ?? '—'),
            sprintf("%-18s %s", 'Marca:', $eq->cpu_marca ?? '—'),
            sprintf("%-18s %s", 'Modelo:', $eq->cpu_modelo ?? '—'),
            sprintf("%-18s %s", 'No. Serie:', $eq->cpu_serie ?? '—'),
            sprintf("%-18s %s", 'Área:', $eq->area ?? '—'),
            sprintf("%-18s %s", 'Responsable:', $responsable),
            sprintf("%-18s %s", 'Estado:', $estado),
            sprintf("%-18s %s", 'IP:', $eq->ipv4 ?: ($eq->ipv4_actual ?? '—')),
            sprintf("%-18s %s", 'MAC:', $eq->mac ?? '—'),
            '',
            $sep,
            'PERIFÉRICOS',
            $sep,
        ];

        $perifericos = [
            'Teclado S/N'    => $eq->teclado_serie,
            'Mouse S/N'      => $eq->mouse_serie,
            'Monitor Marca'  => $eq->monitor_marca,
            'Monitor Modelo' => $eq->monitor_modelo,
            'Monitor S/N'    => $eq->monitor_serie,
            'No-Break Marca' => $eq->nobreak_marca,
            'No-Break Mod.'  => $eq->nobreak_modelo,
            'No-Break S/N'   => $eq->nobreak_serie,
            'Cargador S/N'   => $eq->cargador_serie,
            'Docking Marca'  => $eq->docking_marca,
            'Docking Modelo' => $eq->docking_modelo,
            'Docking S/N'    => $eq->docking_serie,
            'Candado'        => $eq->candado,
        ];

        $hayPeriferico = false;
        foreach ($perifericos as $label => $value) {
            if (!empty(trim((string) $value))) {
                $lines[]       = sprintf("  %-16s %s", $label . ':', $value);
                $hayPeriferico = true;
            }
        }
        if (!$hayPeriferico) {
            $lines[] = '  Sin periféricos registrados.';
        }

        if (!empty($eq->observaciones)) {
            $lines[] = '';
            $lines[] = $sep;
            $lines[] = 'OBSERVACIONES';
            $lines[] = $sep;
            $lines[] = $eq->observaciones;
        }

        $lines[] = '';
        $lines[] = str_repeat('═', 44);
        $lines[] = 'Generado: ' . now()->format('d/m/Y H:i');
        $lines[] = 'Sistema de Inventario IMJUVE';

        return implode("\n", $lines);
    }
}
