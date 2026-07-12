<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;

class InsumosExporter extends BaseExporter
{
    public function headers(): array
    {
        return ['Part Number', 'Descripción', 'Stock Mín.', 'Stock Actual', 'Estado'];
    }

    public function rows(array $filters): array
    {
        $query = DB::table('insumos')->orderBy('nombre_insumo');

        if (!empty($filters['f-ins-texto'])) {
            $q = $filters['f-ins-texto'];
            $query->where(function ($w) use ($q) {
                $w->where('nombre_insumo', 'like', "%{$q}%")
                  ->orWhere('numero_parte', 'like', "%{$q}%");
            });
        }

        if (!empty($filters['f-ins-stock'])) {
            match ($filters['f-ins-stock']) {
                'critico' => $query->whereRaw('stock_actual <= stock_minimo'),
                'ok'      => $query->whereRaw('stock_actual > stock_minimo'),
                default   => null,
            };
        }

        $rows = [];
        foreach ($query->get() as $ins) {
            $rows[] = [
                $ins->numero_parte ?? '—',
                $ins->nombre_insumo,
                $ins->stock_minimo,
                $ins->stock_actual,
                $ins->stock_actual <= $ins->stock_minimo ? 'Crítico' : 'OK',
            ];
        }

        return $rows;
    }

    public function filename(): string
    {
        return 'kardex_insumos';
    }
}
