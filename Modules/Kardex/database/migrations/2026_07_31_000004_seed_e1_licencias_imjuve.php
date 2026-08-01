<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('licencias') || !Schema::hasTable('licencia_users')) {
            return;
        }

        $usuarios = DB::table('users')
            ->where('role', 'user')
            ->whereRaw('LOWER(email) LIKE ?', ['%@imjuventud.gob.mx'])
            ->select('id', 'email')
            ->get();

        foreach ($usuarios as $user) {
            $licId = DB::table('licencias')->where('correo', $user->email)->value('id');

            if (!$licId) {
                $licId = DB::table('licencias')->insertGetId([
                    'correo'       => $user->email,
                    'tipo'         => 'E1',
                    'max_usuarios' => 1,
                    'max_equipos'  => 0,
                    'estado'       => 'Activa',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }

            DB::table('licencia_users')->updateOrInsert(
                ['licencia_id' => $licId, 'user_id' => $user->id],
                ['created_at'  => now()]
            );
        }
    }

    public function down(): void
    {
        // La migración inversa solo limpia las licencias E1 de dominio imjuventud.gob.mx
        if (!Schema::hasTable('licencias')) return;

        $ids = DB::table('licencias')
            ->where('tipo', 'E1')
            ->whereRaw('LOWER(correo) LIKE ?', ['%@imjuventud.gob.mx'])
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('licencia_users')->whereIn('licencia_id', $ids)->delete();
            DB::table('licencias')->whereIn('id', $ids)->delete();
        }
    }
};
