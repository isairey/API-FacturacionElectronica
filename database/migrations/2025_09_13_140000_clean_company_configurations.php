<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Eliminar configuraciones que ya no se manejan en el sistema
        $configsToRemove = [
            'sunat_credentials',
            'service_endpoints', 
            'file_settings',
            'summary_settings',
            'void_settings',
            'notification_settings',
            'security_settings'
        ];
        
        foreach ($configsToRemove as $configType) {
            DB::table('company_configurations')
                ->where('config_type', $configType)
                ->delete();
        }
        
        // Actualizar la estructura del ENUM removiendo tipos no utilizados
        DB::statement("ALTER TABLE company_configurations MODIFY COLUMN config_type ENUM(
            'tax_settings',
            'invoice_settings', 
            'gre_settings',
            'document_settings'
        ) NOT NULL");
        
        // Asegurar que existen configuraciones básicas para empresas existentes
        $companies = DB::table('companies')->where('activo', 1)->get();
        
        foreach ($companies as $company) {
            // Configuración tax_settings por defecto
            DB::table('company_configurations')->updateOrInsert(
                [
                    'company_id' => $company->id,
                    'config_type' => 'tax_settings',
                    'environment' => 'general',
                    'service_type' => 'general'
                ],
                [
                    'config_data' => json_encode([
                        'igv_porcentaje' => 18,
                        'icbper_monto' => 0.5,
                        'isc_porcentaje' => 0,
                        'ivap_porcentaje' => 4,
                        'decimales_cantidad' => 10,
                        'decimales_precio_unitario' => 10,
                        'redondeo_automatico' => true,
                        'validar_ruc_cliente' => true,
                        'permitir_precio_cero' => false,
                        'incluir_leyenda_monto' => true
                    ]),
                    'is_active' => true,
                    'description' => 'Configuración de impuestos por defecto',
                    'priority' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
            
            // Configuración document_settings por defecto
            DB::table('company_configurations')->updateOrInsert(
                [
                    'company_id' => $company->id,
                    'config_type' => 'document_settings',
                    'environment' => 'general',
                    'service_type' => 'general'
                ],
                [
                    'config_data' => json_encode([
                        'generar_xml_automatico' => true,
                        'generar_pdf_automatico' => true,
                        'enviar_sunat_automatico' => false,
                        'formato_pdf_default' => 'a4',
                        'orientacion_pdf_default' => 'portrait',
                        'incluir_qr_pdf' => true,
                        'incluir_hash_pdf' => true,
                        'logo_en_pdf' => true
                    ]),
                    'is_active' => true,
                    'description' => 'Configuración de documentos por defecto',
                    'priority' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
            
            // Configuración invoice_settings por defecto
            DB::table('company_configurations')->updateOrInsert(
                [
                    'company_id' => $company->id,
                    'config_type' => 'invoice_settings',
                    'environment' => 'general',
                    'service_type' => 'general'
                ],
                [
                    'config_data' => json_encode([
                        'ubl_version' => '2.1',
                        'formato_numero' => 'F###-########',
                        'moneda_default' => 'PEN',
                        'tipo_operacion_default' => '0101',
                        'incluir_leyendas_automaticas' => true,
                        'envio_automatico' => false
                    ]),
                    'is_active' => true,
                    'description' => 'Configuración de facturación por defecto',
                    'priority' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
            
            // Configuración gre_settings por defecto
            DB::table('company_configurations')->updateOrInsert(
                [
                    'company_id' => $company->id,
                    'config_type' => 'gre_settings',
                    'environment' => 'general',
                    'service_type' => 'general'
                ],
                [
                    'config_data' => json_encode([
                        'peso_default_kg' => 1.0,
                        'bultos_default' => 1,
                        'modalidad_transporte_default' => '01',
                        'motivo_traslado_default' => '01',
                        'verificacion_automatica' => true
                    ]),
                    'is_active' => true,
                    'description' => 'Configuración de guías de remisión por defecto',
                    'priority' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }
    }

    public function down(): void
    {
        // Revertir cambios - restaurar ENUM original
        DB::statement("ALTER TABLE company_configurations MODIFY COLUMN config_type ENUM(
            'sunat_credentials',
            'service_endpoints',
            'tax_settings',
            'invoice_settings',
            'gre_settings',
            'file_settings',
            'document_settings',
            'summary_settings',
            'void_settings',
            'notification_settings',
            'security_settings'
        ) NOT NULL");
    }
};