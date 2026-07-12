<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TicketsExporter extends BaseExporter
{
    private const ESTADOS = [0 => 'Abierto', 1 => 'Atendiendo', 2 => 'Cerrado'];

    public function headers(): array
    {
        return [
            'ID Folio', 'Solicitante', 'Correo', 'Área', 'Tipo',
            'Descripción', 'Estado', 'Creado', 'Atendido', 'Cerrado',
            'Técnico', 'Comentarios',
        ];
    }

    public function rows(array $filters): array
    {
        $query = DB::table('tickets')->orderByDesc('created_at');

        if (!empty($filters['filtro-area'])) {
            $query->where('area', $filters['filtro-area']);
        }

        if (isset($filters['filtro-estado']) && $filters['filtro-estado'] !== '') {
            $query->where('estado', (int) $filters['filtro-estado']);
        }

        if (!empty($filters['filtro-fecha'])) {
            $dias = (int) $filters['filtro-fecha'];
            if ($dias > 0) {
                $query->where('created_at', '>=', now()->subDays($dias)->toDateTimeString());
            }
        }

        $rows = [];
        foreach ($query->get() as $t) {
            $rows[] = [
                $t->id,
                $t->nombre ?? '—',
                $t->correo ?? '—',
                $t->area ?? '—',
                $t->tipo ?? '—',
                $t->descripcion ?? '—',
                self::ESTADOS[$t->estado] ?? $t->estado,
                $t->created_at  ? Carbon::parse($t->created_at)->format('d/m/Y H:i')  : '—',
                $t->atendido_at ? Carbon::parse($t->atendido_at)->format('d/m/Y H:i') : '—',
                $t->cerrado_at  ? Carbon::parse($t->cerrado_at)->format('d/m/Y H:i')  : '—',
                $t->atendido_by ?? '—',
                $t->comentarios ?? '',
            ];
        }

        return $rows;
    }

    public function filename(): string
    {
        return 'tickets';
    }
}
