<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id('id_empleado');
            $table->string('nombre', 80);
            $table->string('apellido_paterno', 80)->nullable();
            $table->string('apellido_materno', 80)->nullable();
            $table->string('puesto', 120)->nullable();
            $table->string('correo', 120)->nullable();
            $table->foreignId('id_departamento')
                  ->nullable()
                  ->constrained('departamentos', 'id_departamento')
                  ->nullOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamp('fecha_alta')->useCurrent();
            $table->timestamp('fecha_baja')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
