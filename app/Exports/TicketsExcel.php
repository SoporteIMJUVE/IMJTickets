<?php

namespace App\Exports;

use App\Models\Ticket;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;

class TicketsExcel implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public $tickets;

    public function __construct($tickets)
    {
        $this->tickets = $tickets;
    }

    public function styles($sheet)
    {
        return [
            // Encabezados en negrita
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Descripción',
            'Tipo',
            'Área',
            'Estado',
            'Creado',
            'Atendido',
            'Cerrado',
        ];
    }

    public function collection()
    {
        return Ticket::whereIn('id', $this->tickets->pluck('id'))->get()->map(function($ticket) {
            return [
                $ticket->id,
                $ticket->nombre,
                $ticket->descripcion,
                $ticket->tipo,
                $ticket->area,
                Ticket::ESTADOS[$ticket->estado] ?? 'Desconocido',
                $ticket->created_at,
                $ticket->atendido_at,
                $ticket->estado === 2 ? $ticket->updated_at : null,
            ];
        });
    }
}
