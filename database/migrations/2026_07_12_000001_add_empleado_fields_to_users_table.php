<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('apellido_paterno', 80)->nullable()->after('name');
            $table->string('apellido_materno', 80)->nullable()->after('apellido_paterno');
            $table->string('puesto', 120)->nullable()->after('role');
            $table->foreignId('id_departamento')
                  ->nullable()
                  ->after('puesto')
                  ->constrained('departamentos', 'id_departamento')
                  ->nullOnDelete();
            $table->boolean('activo')->default(true)->after('id_departamento');
            $table->timestamp('fecha_alta')->nullable()->after('activo');
            $table->timestamp('fecha_baja')->nullable()->after('fecha_alta');

            $table->string('role', 20)->default('user')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_departamento');
            $table->dropColumn([
                'apellido_paterno',
                'apellido_materno',
                'puesto',
                'activo',
                'fecha_alta',
                'fecha_baja',
            ]);

            $table->string('role', 20)->default('tecnico')->change();
        });
    }
};
