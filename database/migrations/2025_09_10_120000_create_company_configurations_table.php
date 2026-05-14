<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            // Tipo de configuración - Solo las esenciales
            $table->enum('config_type', [
                'tax_settings',            // Configuraciones de impuestos (IGV, ICBPER, etc.)
                'invoice_settings',        // Configuraciones específicas de facturación
                'gre_settings',            // Configuraciones específicas de guías de remisión
                'document_settings'        // Configuraciones de documentos (PDF, XML)
            ]);
            
            // Ambiente al que aplica la configuración
            $table->enum('environment', [
                'general',      // Aplica a todos los ambientes
                'beta',         // Solo ambiente de pruebas
                'produccion'    // Solo ambiente de producción
            ])->default('general');
            
            // Servicio específico (para credenciales)
            $table->enum('service_type', [
                'general',              // Configuración general
                'facturacion',          // Facturas, boletas, notas
                'guias_remision',       // Guías de remisión electrónica
                'resumenes_diarios',    // Resúmenes diarios
                'comunicaciones_baja',  // Comunicaciones de baja
                'retenciones'           // Retenciones
            ])->nullable();
            
            // Datos de configuración en formato JSON
            $table->json('config_data');
            
            // Configuración activa
            $table->boolean('is_active')->default(true);
            
            // Descripción opcional de la configuración
            $table->string('description')->nullable();
            
            // Orden de prioridad (para configuraciones conflictivas)
            $table->integer('priority')->default(0);
            
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['company_id', 'config_type']);
            $table->index(['company_id', 'environment']);
            $table->index(['company_id', 'config_type', 'environment']);
            $table->index(['company_id', 'service_type', 'environment']);
            
            // Constraint único para evitar duplicados de configuración
            $table->unique(['company_id', 'config_type', 'environment', 'service_type'], 'unique_company_config');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_configurations');
    }
};