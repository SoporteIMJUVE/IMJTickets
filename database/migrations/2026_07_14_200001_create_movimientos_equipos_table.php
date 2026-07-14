<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_equipos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_activo');           // 'equipo' | 'impresora'
            $table->unsignedBigInteger('activo_id'); // id en la tabla correspondiente
            $table->string('tipo_evento');           // Entrada, Asignación, Reasignación, Mantenimiento, Baja, Reingreso, Almacén
            $table->string('origen')->nullable();    // propietario/ubicación anterior
            $table->string('destino')->nullable();   // propietario/ubicación nuevo
            $table->foreignId('user_from_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ticket_ref')->nullable();
            $table->string('estado_equipo')->nullable(); // Nuevo, Operativo, En Reparación, Obsoleto
            $table->text('notas')->nullable();
            $table->foreignId('registrado_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['tipo_activo', 'activo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_equipos');
    }
};
