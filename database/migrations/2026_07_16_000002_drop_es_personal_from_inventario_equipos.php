<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Revertimos la columna es_personal.
// La propiedad personal se deriva ahora del historial Kardex:
// si el equipo tiene un evento 'Entrada' en movimientos_equipos
// fue ingresado con resguardo (es del IMJUVE);
// si no tiene 'Entrada', fue registrado directamente desde CRM (equipo personal).
return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventario_equipos', function (Blueprint $table) {
            $table->dropColumn('es_personal');
        });
    }

    public function down(): void
    {
        Schema::table('inventario_equipos', function (Blueprint $table) {
            $table->boolean('es_personal')->default(false)->after('observaciones');
        });
    }
};
