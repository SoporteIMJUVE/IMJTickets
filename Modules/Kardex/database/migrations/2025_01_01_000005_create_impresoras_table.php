<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impresoras', function (Blueprint $table) {
            $table->id('id_impresora');
            $table->text('area')->nullable();
            $table->string('marca', 60)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('firmware', 60)->nullable();
            $table->string('serie', 60)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('id_empleado')
                  ->nullable()
                  ->constrained('empleados', 'id_empleado')
                  ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impresoras');
    }
};
