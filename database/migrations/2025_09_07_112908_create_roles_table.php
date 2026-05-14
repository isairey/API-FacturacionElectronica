<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // super_admin, company_admin, company_user, api_client, read_only
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->json('permissions')->nullable(); // Array de permisos rápidos
            $table->boolean('is_system')->default(false); // No se puede eliminar
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->index(['name', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
