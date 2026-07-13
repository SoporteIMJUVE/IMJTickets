<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Mismo motivo que en inventario_equipos: evitar que una impresora comparta
// IP con otro dispositivo mediante una FK real a inventario_ips_completo.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('impresoras', function (Blueprint $table) {
            $table->foreignId('ip_id')
                  ->nullable()
                  ->after('ip_address')
                  ->constrained('inventario_ips_completo')
                  ->nullOnDelete();
            $table->unique('ip_id');
        });

        DB::statement("
            UPDATE impresoras
            SET ip_id = (SELECT id FROM inventario_ips_completo WHERE ip = impresoras.ip_address)
            WHERE ip_address IS NOT NULL AND ip_address <> ''
        ");
    }

    public function down(): void
    {
        Schema::table('impresoras', function (Blueprint $table) {
            $table->dropUnique(['ip_id']);
            $table->dropConstrainedForeignId('ip_id');
        });
    }
};
