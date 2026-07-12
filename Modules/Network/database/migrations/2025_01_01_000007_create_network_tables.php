<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cat_rangos_ips', function (Blueprint $table) {
            $table->id('id_rango');
            $table->string('area_nombre', 120);
            $table->string('siglas', 20)->nullable();
            $table->string('ip_inicial', 20)->nullable();
            $table->string('ip_final', 20)->nullable();
            $table->integer('capacidad_total')->nullable();
            $table->integer('ocupadas')->default(0);
            $table->integer('libres')->default(0);
            $table->timestamps();
        });

        Schema::create('inventario_ips_completo', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->unique();
            $table->text('usuario')->nullable();
            $table->string('tipo_equipo', 60)->nullable();
            $table->string('institucional_o_personal', 30)->nullable();
            $table->string('marca', 60)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('serie', 100)->nullable();
            $table->string('mac', 30)->nullable();
            $table->string('tipo_conexion', 30)->nullable();
            $table->string('config_red', 30)->nullable();
            $table->string('area_excel', 120)->nullable();
            $table->string('departamento_pestana', 120)->nullable();
            $table->string('restricciones', 30)->nullable();
            // Permisos individuales
            $table->string('youtube', 10)->nullable();
            $table->string('vimeo', 10)->nullable();
            $table->string('spotify', 10)->nullable();
            $table->string('otros_streaming', 10)->nullable();
            $table->string('facebook', 10)->nullable();
            $table->string('tiktok', 10)->nullable();
            $table->string('instagram', 10)->nullable();
            $table->string('whatsapp_web', 10)->nullable();
            $table->string('otra_red_social', 10)->nullable();
            $table->string('sitios_gub', 10)->nullable();
            $table->string('noticias', 10)->nullable();
            $table->string('otro_permiso', 10)->nullable();
            $table->string('estatus', 30)->default('Libre');
            $table->text('observaciones')->nullable();
            $table->foreignId('id_empleado')
                  ->nullable()
                  ->constrained('empleados', 'id_empleado')
                  ->nullOnDelete();
            $table->timestamps();

            $table->index('departamento_pestana');
            $table->index('estatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_ips_completo');
        Schema::dropIfExists('cat_rangos_ips');
    }
};
