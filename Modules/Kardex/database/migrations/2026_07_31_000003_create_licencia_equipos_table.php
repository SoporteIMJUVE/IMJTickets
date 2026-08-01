<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licencia_equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('licencia_id')->constrained('licencias')->cascadeOnDelete();
            $table->foreignId('equipo_id')->constrained('inventario_equipos')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['licencia_id', 'equipo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licencia_equipos');
    }
};
