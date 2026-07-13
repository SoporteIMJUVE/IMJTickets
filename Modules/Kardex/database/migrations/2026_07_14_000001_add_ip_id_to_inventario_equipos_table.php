<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// inventario_ips_completo ya es el registro maestro de facto (IP única);
// esta FK evita que dos equipos terminen apuntando a la misma IP, algo que
// hoy nada impide (ipv4 es texto libre sin ninguna relación real).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventario_equipos', function (Blueprint $table) {
            $table->foreignId('ip_id')
                  ->nullable()
                  ->after('ipv4')
                  ->constrained('inventario_ips_completo')
                  ->nullOnDelete();
            $table->unique('ip_id');
        });

        DB::statement("
            UPDATE inventario_equipos
            SET ip_id = (SELECT id FROM inventario_ips_completo WHERE ip = inventario_equipos.ipv4)
            WHERE ipv4 IS NOT NULL AND ipv4 <> ''
        ");
    }

    public function down(): void
    {
        Schema::table('inventario_equipos', function (Blueprint $table) {
            $table->dropUnique(['ip_id']);
            $table->dropConstrainedForeignId('ip_id');
        });
    }
};
