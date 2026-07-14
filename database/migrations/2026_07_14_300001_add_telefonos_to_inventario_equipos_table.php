<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventario_equipos', function (Blueprint $table) {
            $table->integer('extension')->nullable()->after('mac');
            $table->string('numero_general', 30)->nullable()->after('extension');
        });

        // Migrar telefonos → inventario_equipos
        DB::table('telefonos')->get()->each(function ($tel) {
            DB::table('inventario_equipos')->insert([
                'tipo'              => 'Telefono',
                'nombre_equipo'     => $tel->extension ? 'Ext. ' . $tel->extension : 'Teléfono',
                'extension'         => $tel->extension,
                'numero_general'    => $tel->numero_general,
                'user_id'           => $tel->user_id,
                'usuario_actual_id' => $tel->user_id,
                'created_at'        => $tel->created_at ?? now(),
                'updated_at'        => $tel->updated_at ?? now(),
            ]);
        });
    }

    public function down(): void
    {
        // Eliminar filas migradas de telefonos
        DB::table('inventario_equipos')->where('tipo', 'Telefono')->delete();

        Schema::table('inventario_equipos', function (Blueprint $table) {
            $table->dropColumn(['extension', 'numero_general']);
        });
    }
};
