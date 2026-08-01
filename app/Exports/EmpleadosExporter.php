<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EmpleadosExporter extends BaseExporter
{
    public function headers(): array
    {
        return [
            'Nombre', 'Correo', 'Departamento / Área',
            'Equipos Asignados', 'No. de Serie', 'Estado', 'Licencia',
        ];
    }

    public function rows(array $filters): array
    {
        $query = DB::table('users')
            ->leftJoin('departamentos', 'users.id_departamento', '=', 'departamentos.id_departamento')
            ->select(
                'users.id as id_empleado',
                DB::raw("TRIM(COALESCE(users.name,'') || ' ' || COALESCE(users.apellido_paterno,'') || ' ' || COALESCE(users.apellido_materno,'')) as nombre_completo"),
                'users.email as correo',
                'departamentos.nombre as departamento',
                'users.activo'
            )
            ->where('users.role', 'user')
            ->orderBy('users.name');

        if (!empty($filters['crm-search'])) {
            $q = $filters['crm-search'];
            $query->where(function ($w) use ($q) {
                $w->where('users.name', 'like', "%{$q}%")
                  ->orWhere('users.apellido_paterno', 'like', "%{$q}%")
                  ->orWhere('users.email', 'like', "%{$q}%");
            });
        }

        if (!empty($filters['filter-depa'])) {
            $query->where(DB::raw('LOWER(departamentos.nombre)'), $filters['filter-depa']);
        }

        if (isset($filters['filter-estado']) && $filters['filter-estado'] !== '') {
            $query->where('users.activo', (int) $filters['filter-estado']);
        }

        $empleados = $query->get();

        $equiposPorEmpleado = DB::table('inventario_equipos')
            ->whereNotNull('user_id')
            ->select('user_id', 'cpu_serie', 'tipo')
            ->get()
            ->groupBy('user_id');

        $licenciasPorUsuario = Schema::hasTable('licencia_users')
            ? DB::table('licencia_users')
                ->join('licencias', 'licencia_users.licencia_id', '=', 'licencias.id')
                ->select('licencia_users.user_id', 'licencias.tipo', 'licencias.correo')
                ->get()
                ->groupBy(fn($r) => (string) $r->user_id)
            : collect();

        $rows = [];
        foreach ($empleados as $emp) {
            $equipos  = $equiposPorEmpleado->get($emp->id_empleado, collect());
            $tipos    = $equipos->pluck('tipo')->filter()->implode(', ') ?: '—';
            $numInvs  = $equipos->isNotEmpty()
                ? $equipos->map(fn($e) => $e->cpu_serie ?: 'Personal')->implode(', ')
                : '—';

            $lics = $licenciasPorUsuario->get((string) $emp->id_empleado, collect());
            if ($lics->isNotEmpty()) {
                $licTexto = $lics->map(fn($l) => "{$l->tipo} ({$l->correo})")->implode(' | ');
            } elseif (str_ends_with(strtolower((string) ($emp->correo ?? '')), '@imjuventud.gob.mx')) {
                $licTexto = 'E1 (' . $emp->correo . ')';
            } else {
                $licTexto = '—';
            }

            $rows[] = [
                trim($emp->nombre_completo),
                $emp->correo ?? '—',
                $emp->departamento ?? '—',
                $tipos,
                $numInvs,
                $emp->activo ? 'Activo' : 'Baja',
                $licTexto,
            ];
        }

        return $rows;
    }

    public function filename(): string
    {
        return 'directorio_empleados';
    }
}
