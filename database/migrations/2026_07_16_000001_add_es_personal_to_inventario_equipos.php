<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventario_equipos', function (Blueprint $table) {
            // true  → equipo propiedad del empleado, no del IMJUVE (sin resguardo)
            // false → equipo institucional con resguardo (default)
            $table->boolean('es_personal')->default(false)->after('observaciones');
        });
    }

    public function down(): void
    {
        Schema::table('inventario_equipos', function (Blueprint $table) {
            $table->dropColumn('es_personal');
        });
    }
};
