<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;

class EmpleadosExporter extends BaseExporter
{
    public function headers(): array
    {
        return [
            'Nombre', 'Correo', 'Departamento / Área',
            'Equipos Asignados', 'No. de Inventario(s)', 'Estado', 'Licencia',
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
            ->select('user_id', 'num_inventario', 'tipo')
            ->get()
            ->groupBy('user_id');

        $rows = [];
        foreach ($empleados as $emp) {
            $equipos  = $equiposPorEmpleado->get($emp->id_empleado, collect());
            $tipos    = $equipos->pluck('tipo')->filter()->implode(', ') ?: '—';
            $numInvs  = $equipos->pluck('num_inventario')->filter()->implode(', ') ?: '—';

            $rows[] = [
                trim($emp->nombre_completo),
                $emp->correo ?? '—',
                $emp->departamento ?? '—',
                $tipos,
                $numInvs,
                $emp->activo ? 'Activo' : 'Baja',
                '', // Licencia — campo pendiente
            ];
        }

        return $rows;
    }

    public function filename(): string
    {
        return 'directorio_empleados';
    }
}
