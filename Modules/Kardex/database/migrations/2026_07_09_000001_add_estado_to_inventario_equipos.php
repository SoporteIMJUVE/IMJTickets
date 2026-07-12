<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventario_equipos', function (Blueprint $table) {
            // null = derivado (Almacén si no hay empleado, Asignado si hay empleado)
            $table->string('estado', 30)->nullable()->after('id_empleado');
        });
    }

    public function down(): void
    {
        Schema::table('inventario_equipos', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
