<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telefonos', function (Blueprint $table) {
            $table->id('id_telefono');
            $table->string('numero_general', 30)->nullable();
            $table->integer('extension')->nullable();
            $table->foreignId('id_empleado')
                  ->nullable()
                  ->constrained('empleados', 'id_empleado')
                  ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telefonos');
    }
};
