<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// LOTE B — confirmado sin uso real (0 de 179 filas pobladas, solo existía
// en validaciones/exports como fallback muerto). Segura de quitar, pero se
// deja sin correr siguiendo el mismo patrón de los demás Lotes B: cambios
// destructivos se despliegan aparte, deliberadamente.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventario_equipos', function (Blueprint $table) {
            $table->dropColumn('ipv4_actual');
        });
    }

    public function down(): void
    {
        Schema::table('inventario_equipos', function (Blueprint $table) {
            $table->string('ipv4_actual', 20)->nullable()->after('ipv4');
        });
    }
};
