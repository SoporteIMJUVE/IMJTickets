<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// LOTE B — destructivo. NO correr hasta verificar en producción que la app
// funciona correctamente sobre `users`/`user_id` (ver plan de migración
// paso 1: fusión users+empleados). Se renombra en vez de borrar como red de
// seguridad; se puede eliminar en una migración de limpieza posterior una
// vez que el cliente confirme que ya no hace falta.
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('empleados', 'empleados_legacy_backup');
    }

    public function down(): void
    {
        Schema::rename('empleados_legacy_backup', 'empleados');
    }
};
