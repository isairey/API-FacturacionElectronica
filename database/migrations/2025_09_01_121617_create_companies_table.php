<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('ruc', 11)->unique();
            $table->string('razon_social');
            $table->string('nombre_comercial')->nullable();
            $table->string('direccion');
            $table->string('ubigeo', 6);
            $table->string('distrito');
            $table->string('provincia');
            $table->string('departamento');
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('web')->nullable();
            
            // Configuración SUNAT
            $table->string('usuario_sol');
            $table->string('clave_sol');
            $table->text('certificado_pem')->nullable();
            $table->string('certificado_password')->nullable();
            
            // Endpoints
            $table->string('endpoint_beta');
            $table->string('endpoint_produccion');
            $table->boolean('modo_produccion')->default(false);
            
            // Logo y configuraciones adicionales
            $table->string('logo_path')->nullable();
            $table->json('configuraciones')->nullable(); // Para configuraciones extras
            
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};