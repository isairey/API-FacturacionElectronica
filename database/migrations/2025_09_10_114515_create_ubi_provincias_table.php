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
        Schema::create('ubi_provincias', function (Blueprint $table) {
            $table->string('id', 6)->primary();
            $table->string('nombre', 255)->default('');
            $table->string('region_id', 6)->default('');
            
            $table->foreign('region_id')->references('id')->on('ubi_regiones');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ubi_provincias');
    }
};
