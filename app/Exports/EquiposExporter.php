<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;

class EquiposExporter extends BaseExporter
{
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
