<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// LOTE B — destructivo. NO correr hasta verificar en producción (ver §5 del
// plan de migración paso 1: fusión users+empleados).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventario_ips_completo', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_empleado');
        });
    }

    public function down(): void
    {
        // Al hacer rollback, la migración de rename ya restauró 'empleados'
        // (se revierte después de esta en el mismo batch).
        Schema::table('inventario_ips_completo', function (Blueprint $table) {
            $table->foreignId('id_empleado')
                  ->nullable()
                  ->constrained('empleados', 'id_empleado')
                  ->nullOnDelete();
        });
    }
};
