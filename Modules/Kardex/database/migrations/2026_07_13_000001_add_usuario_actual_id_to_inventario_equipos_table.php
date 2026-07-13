<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Separa "responsable" (user_id, quien firma el resguardo) de "usuario actual"
// (quien usa el equipo día a día). Por defecto son la misma persona; cambiar
// el usuario actual va a ser una función de autoservicio del propio usuario
// logueado (pendiente del sistema de login, paso 2) — aquí solo se prepara
// la columna.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventario_equipos', function (Blueprint $table) {
            $table->foreignId('usuario_actual_id')
                  ->nullable()
                  ->after('user_id')
                  ->constrained('users')
                  ->nullOnDelete();
        });

        DB::table('inventario_equipos')
            ->whereNotNull('user_id')
            ->update(['usuario_actual_id' => DB::raw('user_id')]);
    }

    public function down(): void
    {
        Schema::table('inventario_equipos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('usuario_actual_id');
        });
    }
};
