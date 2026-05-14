<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Remover el campo configuraciones ya que ahora usamos la tabla separada
            $table->dropColumn('configuraciones');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Restaurar el campo configuraciones
            $table->json('configuraciones')->nullable()->after('logo_path');
        });
    }
};