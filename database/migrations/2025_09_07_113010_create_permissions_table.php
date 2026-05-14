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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // invoices.create, invoices.view, companies.manage, etc.
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('category')->default('general'); // invoices, boletas, companies, system, etc.
            $table->boolean('is_system')->default(false); // Permisos críticos del sistema
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->index(['category', 'active']);
            $table->index(['name', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
