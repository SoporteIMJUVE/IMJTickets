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
    public $respaldo;

    public function __construct($tickets, $respaldo = false)
    {
        $this->tickets = $tickets;
        $this->respaldo = $respaldo;
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
        if($this->respaldo) {
            return [
                'id',
                'nombre',
                'correo',
                'area',
                'tipo',
                'descripcion', 
                'estado',
                'created_at',
                'updated_at',
                'atendido_at', 
                'cerrado_at',
                'comentarios',
                'atendido_by',
                'cerrado_by'
            ];
        }

        return [
            'ID',
            'Nombre',
            'Descripción',
            'Comentarios',
            'Tipo',
            'Área',
            'Estado',
            'Creado',
            'Cerrado',
        ];
    }

    public function collection()
    {
        return Ticket::whereIn('id', $this->tickets->pluck('id'))->get()->map(function($ticket) {

            $estadoNumero = is_numeric($ticket->estado) ? (string)$ticket->estado : "0";

            $fechaTexto = function($fecha) {
                return $fecha ? \Carbon\Carbon::parse($fecha)->format('Y-m-d H:i:s') : null;
            };
            
            if($this->respaldo) {
                return [
                    $ticket->id,
                    $ticket->nombre,
                    $ticket->correo,
                    $ticket->area,
                    $ticket->tipo,
                    $ticket->descripcion, 
                    $estadoNumero,
                    $fechaTexto($ticket->created_at),
                    $fechaTexto($ticket->updated_at),
                    $fechaTexto($ticket->atendido_at), 
                    $fechaTexto($ticket->cerrado_at),
                    $ticket->comentarios,
                    $ticket->atendido_by,
                    $ticket->cerrado_by
                ];
            }
            return [
                $ticket->id,
                $ticket->nombre,
                $ticket->descripcion,
                $ticket->comentarios,
                $ticket->tipo,
                $ticket->area,
                Ticket::ESTADOS[$ticket->estado] ?? 'Desconocido',
                $ticket->created_at,
                $ticket->cerrado_at,
            ];
        });
    }
}
