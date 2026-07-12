<?php

namespace Modules\Tickets\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    const ESTADOS = [0 => 'Abierto', 1 => 'Atendiendo', 2 => 'Cerrado'];

    protected $fillable = [
        'nombre', 'correo', 'ip', 'mac',
        'area', 'tipo', 'descripcion', 'estado', 'comentarios',
        'atendido_at', 'atendido_by', 'cerrado_at', 'cerrado_by',
    ];

    protected $casts = ['estado' => 'integer'];

    public function getEstadoTxtAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? '—';
    }
}
