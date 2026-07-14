<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KardexMovimiento
{
    public static function registrar(
        string  $tipo_activo,
        int     $activo_id,
        string  $tipo_evento,
        ?string $origen       = null,
        ?string $destino      = null,
        ?int    $user_from_id = null,
        ?int    $user_to_id   = null,
        ?string $ticket_ref   = null,
        ?string $estado_equipo = null,
        ?string $notas        = null,
        ?int    $registrado_by = null,
    ): void {
        DB::table('movimientos_equipos')->insert([
            'tipo_activo'   => $tipo_activo,
            'activo_id'     => $activo_id,
            'tipo_evento'   => $tipo_evento,
            'origen'        => $origen,
            'destino'       => $destino,
            'user_from_id'  => $user_from_id,
            'user_to_id'    => $user_to_id,
            'ticket_ref'    => $ticket_ref,
            'estado_equipo' => $estado_equipo,
            'notas'         => $notas,
            'registrado_by' => $registrado_by ?? Auth::id(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }
}
