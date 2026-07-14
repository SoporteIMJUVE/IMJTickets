<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('impresoras', function (Blueprint $table) {
            $table->string('estado')->nullable()->after('ip_id');
        });
    }

    public function down(): void
    {
        Schema::table('impresoras', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
