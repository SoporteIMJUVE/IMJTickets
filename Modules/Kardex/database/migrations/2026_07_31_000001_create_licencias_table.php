<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licencias', function (Blueprint $table) {
            $table->id();
            $table->string('correo', 150)->unique();
            $table->string('tipo', 50);
            $table->integer('max_usuarios')->default(1);
            $table->integer('max_equipos')->default(0);
            $table->text('area')->nullable();
            $table->string('estado', 30)->default('Activa');
            $table->date('caducidad')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licencias');
    }
};
