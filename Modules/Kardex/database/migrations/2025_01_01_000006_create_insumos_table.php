<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insumos', function (Blueprint $table) {
            $table->id('id_insumo');
            $table->string('nombre_insumo', 120);
            $table->string('numero_parte', 60)->nullable();
            $table->integer('stock_minimo')->default(0);
            $table->integer('stock_maximo')->default(0);
            $table->integer('stock_actual')->default(0);
            $table->timestamps();
        });

        Schema::create('suministros', function (Blueprint $table) {
            $table->id('id_suministro');
            $table->foreignId('id_insumo')
                  ->nullable()
                  ->constrained('insumos', 'id_insumo')
                  ->nullOnDelete();
            $table->foreignId('id_departamento')
                  ->nullable()
                  ->constrained('departamentos', 'id_departamento')
                  ->nullOnDelete();
            $table->date('fecha_solicitud')->useCurrent();
            $table->integer('cantidad_requerida')->nullable();
            $table->integer('cantidad_entregada')->nullable();
            $table->string('estatus', 60)->default('PENDIENTE DE ENTREGA');
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suministros');
        Schema::dropIfExists('insumos');
    }
};
