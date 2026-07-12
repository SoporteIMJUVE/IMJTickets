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
        $query = DB::table('empleados')
            ->leftJoin('departamentos', 'empleados.id_departamento', '=', 'departamentos.id_departamento')
            ->select(
                'empleados.id_empleado',
                DB::raw("TRIM(COALESCE(empleados.nombre,'') || ' ' || COALESCE(empleados.apellido_paterno,'') || ' ' || COALESCE(empleados.apellido_materno,'')) as nombre_completo"),
                'empleados.correo',
                'departamentos.nombre as departamento',
                'empleados.activo'
            )
            ->orderBy('empleados.nombre');

        if (!empty($filters['crm-search'])) {
            $q = $filters['crm-search'];
            $query->where(function ($w) use ($q) {
                $w->where('empleados.nombre', 'like', "%{$q}%")
                  ->orWhere('empleados.apellido_paterno', 'like', "%{$q}%")
                  ->orWhere('empleados.correo', 'like', "%{$q}%");
            });
        }

        if (!empty($filters['filter-depa'])) {
            $query->where(DB::raw('LOWER(departamentos.nombre)'), $filters['filter-depa']);
        }

        if (isset($filters['filter-estado']) && $filters['filter-estado'] !== '') {
            $query->where('empleados.activo', (int) $filters['filter-estado']);
        }

        $empleados = $query->get();

        $equiposPorEmpleado = DB::table('inventario_equipos')
            ->whereNotNull('id_empleado')
            ->select('id_empleado', 'num_inventario', 'tipo')
            ->get()
            ->groupBy('id_empleado');

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
