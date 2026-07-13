<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Paso 2: código de recuperación. El hash llega ya calculado desde el
// navegador (ver Doc/ paso 2) — se guarda tal cual, sin volver a hashear,
// porque ya es el mismo valor que la persona guardó en su QR/PDF.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('recovery_code_hash', 64)->nullable()->unique()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['recovery_code_hash']);
            $table->dropColumn('recovery_code_hash');
        });
    }
};
