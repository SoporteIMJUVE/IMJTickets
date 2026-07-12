<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_equipos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 25);                   // Laptop | PC Avanzada | PC Especializada
            $table->integer('consecutivo')->nullable();
            $table->string('num_inventario', 30)->nullable();
            $table->string('nombre_equipo', 60)->nullable();
            $table->text('nombre_usuario')->nullable();
            $table->string('perfil', 100)->nullable();
            $table->text('area')->nullable();
            // CPU
            $table->string('cpu_marca', 60)->nullable();
            $table->string('cpu_modelo', 100)->nullable();
            $table->string('cpu_serie', 100)->nullable();
            // Periféricos
            $table->string('teclado_serie', 100)->nullable();
            $table->string('mouse_serie', 100)->nullable();
            $table->string('monitor_marca', 60)->nullable();
            $table->string('monitor_modelo', 100)->nullable();
            $table->string('monitor_serie', 100)->nullable();
            $table->string('nobreak_marca', 60)->nullable();
            $table->string('nobreak_modelo', 100)->nullable();
            $table->string('nobreak_serie', 100)->nullable();
            // Laptop específico
            $table->string('cargador_serie', 100)->nullable();
            $table->string('docking_marca', 60)->nullable();
            $table->string('docking_modelo', 100)->nullable();
            $table->string('docking_serie', 100)->nullable();
            $table->string('candado', 50)->nullable();
            // Red
            $table->string('ipv4', 20)->nullable();
            $table->string('ipv4_actual', 20)->nullable();
            $table->string('mac', 30)->nullable();
            // Estado
            $table->string('responsiva', 50)->nullable();
            $table->string('check_entrega', 50)->nullable();
            $table->text('observaciones')->nullable();
            // FK
            $table->foreignId('id_empleado')
                  ->nullable()
                  ->constrained('empleados', 'id_empleado')
                  ->nullOnDelete();
            $table->timestamps();
            $table->index('tipo');
            $table->index('area');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_equipos');
    }
};
