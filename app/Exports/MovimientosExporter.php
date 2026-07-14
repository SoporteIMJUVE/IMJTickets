<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;

class MovimientosExporter extends BaseExporter
{
    public function headers(): array
    {
        return ['Fecha', 'Tipo activo', 'No. Serie', 'Activo', 'Evento', 'Origen', 'Destino', 'Estado equipo', 'Notas', 'Registrado por'];
    }

    public function rows(array $filters): array
    {
        $query = DB::table('movimientos_equipos')
            ->leftJoin('users as reg', 'movimientos_equipos.registrado_by', '=', 'reg.id')
            ->select('movimientos_equipos.*', 'reg.email as registrado_email')
            ->orderByDesc('movimientos_equipos.created_at');

        if (!empty($filters['f-mov-activo'])) {
            $query->where('tipo_activo', $filters['f-mov-activo']);
        }
        if (!empty($filters['f-mov-evento'])) {
            $query->where('tipo_evento', $filters['f-mov-evento']);
        }
        if (!empty($filters['f-mov-texto'])) {
            $q = strtolower($filters['f-mov-texto']);
            $query->where(function ($w) use ($q) {
                $w->where(DB::raw('LOWER(COALESCE(origen,""))'),  'like', "%{$q}%")
                  ->orWhere(DB::raw('LOWER(COALESCE(destino,""))'), 'like', "%{$q}%")
                  ->orWhere(DB::raw('LOWER(COALESCE(notas,""))'),   'like', "%{$q}%");
            });
        }

        return $query->get()->map(function ($m) {
            // Resolver serie y descripción del activo
            if ($m->tipo_activo === 'equipo') {
                $eq = DB::table('inventario_equipos')->where('id', $m->activo_id)
                    ->select('cpu_serie', 'cpu_marca', 'cpu_modelo')->first();
                $serie = $eq->cpu_serie ?? '—';
                $desc  = trim(($eq->cpu_marca ?? '') . ' ' . ($eq->cpu_modelo ?? ''));
            } else {
                $imp = DB::table('impresoras')->where('id_impresora', $m->activo_id)
                    ->select('serie', 'marca', 'modelo')->first();
                $serie = $imp->serie ?? '—';
                $desc  = trim(($imp->marca ?? '') . ' ' . ($imp->modelo ?? ''));
            }

            return [
                $m->created_at,
                $m->tipo_activo === 'equipo' ? 'Equipo' : 'Impresora',
                $serie,
                $desc,
                $m->tipo_evento,
                $m->origen,
                $m->destino,
                $m->estado_equipo,
                $m->notas,
                $m->registrado_email,
            ];
        })->toArray();
    }

    public function filename(): string
    {
        return 'movimientos_inventario';
    }
}
