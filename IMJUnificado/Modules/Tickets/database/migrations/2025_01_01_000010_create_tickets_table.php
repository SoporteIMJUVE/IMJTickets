<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('correo');
            $table->string('area');
            $table->string('tipo');
            $table->text('descripcion');
            $table->tinyInteger('estado')->default(0);
            $table->text('comentarios')->nullable();
            $table->timestamp('atendido_at')->nullable();
            $table->string('atendido_by')->nullable();
            $table->timestamp('cerrado_at')->nullable();
            $table->string('cerrado_by')->nullable();
            $table->timestamps();
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
        });

        Schema::create('tipos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
        });

        // empleados se crea en la migración CRM (000002)
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('tickets');
    }
};
