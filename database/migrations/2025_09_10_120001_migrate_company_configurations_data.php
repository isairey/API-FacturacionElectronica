<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Company;
use App\Models\CompanyConfiguration;

return new class extends Migration
{
    public function up(): void
    {
        // Migrar datos existentes del campo configuraciones a la nueva tabla
        $companies = DB::table('companies')->whereNotNull('configuraciones')->get();

        foreach ($companies as $companyData) {
            $configurations = json_decode($companyData->configuraciones, true);
            
            if (!$configurations) {
                continue;
            }

            // Migrar credenciales GRE si existen
            if (isset($configurations['credenciales_gre'])) {
                $this->migrateGreCredentials($companyData->id, $configurations['credenciales_gre']);
            }

            // Migrar endpoints de servicios SUNAT
            if (isset($configurations['servicios_sunat'])) {
                $this->migrateServiceEndpoints($companyData->id, $configurations['servicios_sunat']);
            }

            // Migrar configuraciones de impuestos
            if (isset($configurations['facturacion'])) {
                $this->migrateTaxSettings($companyData->id, $configurations['facturacion']);
            }

            // Migrar configuraciones de guías de remisión
            if (isset($configurations['guias_remision'])) {
                $this->migrateGreSettings($companyData->id, $configurations['guias_remision']);
            }

            // Migrar configuraciones de documentos
            if (isset($configurations['documentos'])) {
                $this->migrateDocumentSettings($companyData->id, $configurations['documentos']);
            }

            // Migrar configuraciones de archivos
            if (isset($configurations['archivos'])) {
                $this->migrateFileSettings($companyData->id, $configurations['archivos']);
            }

            // Migrar configuraciones adicionales
            $additionalConfigs = [
                'resumenes_diarios' => 'summary_settings',
                'comunicaciones_baja' => 'void_settings',
                'notificaciones' => 'notification_settings',
                'seguridad' => 'security_settings',
            ];

            foreach ($additionalConfigs as $oldKey => $configType) {
                if (isset($configurations[$oldKey])) {
                    $this->migrateGenericConfiguration(
                        $companyData->id,
                        $configType,
                        $configurations[$oldKey],
                        "Configuraciones de {$oldKey} migradas automáticamente"
                    );
                }
            }
        }
    }

    public function down(): void
    {
        // En el rollback, restaurar las configuraciones al campo JSON
        $companies = Company::with('configurations')->get();

        foreach ($companies as $company) {
            $configurations = [];

            foreach ($company->configurations as $config) {
                switch ($config->config_type) {
                    case 'sunat_credentials':
                        if ($config->service_type === 'guias_remision') {
                            $configurations['credenciales_gre'][$config->environment] = $config->config_data;
                        }
                        break;

                    case 'service_endpoints':
                        $configurations['servicios_sunat'][$config->service_type][$config->environment] = $config->config_data;
                        break;

                    case 'tax_settings':
                        $configurations['facturacion'] = $config->config_data;
                        break;

                    case 'gre_settings':
                        $configurations['guias_remision'] = $config->config_data;
                        break;

                    case 'document_settings':
                        $configurations['documentos'] = $config->config_data;
                        break;

                    case 'file_settings':
                        $configurations['archivos'] = $config->config_data;
                        break;

                    case 'summary_settings':
                        $configurations['resumenes_diarios'] = $config->config_data;
                        break;

                    case 'void_settings':
                        $configurations['comunicaciones_baja'] = $config->config_data;
                        break;

                    case 'notification_settings':
                        $configurations['notificaciones'] = $config->config_data;
                        break;

                    case 'security_settings':
                        $configurations['seguridad'] = $config->config_data;
                        break;
                }
            }

            if (!empty($configurations)) {
                DB::table('companies')
                    ->where('id', $company->id)
                    ->update(['configuraciones' => json_encode($configurations)]);
            }
        }

        // Eliminar las configuraciones de la nueva tabla
        CompanyConfiguration::truncate();
    }

    private function migrateGreCredentials(int $companyId, array $greCredentials): void
    {
        foreach (['beta', 'produccion'] as $environment) {
            if (isset($greCredentials[$environment])) {
                CompanyConfiguration::create([
                    'company_id' => $companyId,
                    'config_type' => 'sunat_credentials',
                    'environment' => $environment,
                    'service_type' => 'guias_remision',
                    'config_data' => $greCredentials[$environment],
                    'description' => "Credenciales GRE para ambiente {$environment} (migradas)",
                    'is_active' => true,
                ]);
            }
        }
    }

    private function migrateServiceEndpoints(int $companyId, array $servicios): void
    {
        $serviceMap = [
            'facturacion' => 'facturacion',
            'guias_remision' => 'guias_remision',
            'resumenes_diarios' => 'resumenes_diarios',
            'comunicaciones_baja' => 'comunicaciones_baja',
        ];

        foreach ($serviceMap as $oldKey => $serviceType) {
            if (isset($servicios[$oldKey])) {
                foreach (['beta', 'produccion'] as $environment) {
                    if (isset($servicios[$oldKey][$environment])) {
                        CompanyConfiguration::create([
                            'company_id' => $companyId,
                            'config_type' => 'service_endpoints',
                            'environment' => $environment,
                            'service_type' => $serviceType,
                            'config_data' => $servicios[$oldKey][$environment],
                            'description' => "Endpoints para {$serviceType} en {$environment} (migrados)",
                            'is_active' => true,
                        ]);
                    }
                }
            }
        }
    }

    private function migrateTaxSettings(int $companyId, array $facturacion): void
    {
        CompanyConfiguration::create([
            'company_id' => $companyId,
            'config_type' => 'tax_settings',
            'environment' => 'general',
            'service_type' => 'general',
            'config_data' => $facturacion,
            'description' => 'Configuraciones de impuestos (migradas)',
            'is_active' => true,
        ]);
    }

    private function migrateGreSettings(int $companyId, array $guiasRemision): void
    {
        CompanyConfiguration::create([
            'company_id' => $companyId,
            'config_type' => 'gre_settings',
            'environment' => 'general',
            'service_type' => 'general',
            'config_data' => $guiasRemision,
            'description' => 'Configuraciones de guías de remisión (migradas)',
            'is_active' => true,
        ]);
    }

    private function migrateDocumentSettings(int $companyId, array $documentos): void
    {
        CompanyConfiguration::create([
            'company_id' => $companyId,
            'config_type' => 'document_settings',
            'environment' => 'general',
            'service_type' => 'general',
            'config_data' => $documentos,
            'description' => 'Configuraciones de documentos (migradas)',
            'is_active' => true,
        ]);
    }

    private function migrateFileSettings(int $companyId, array $archivos): void
    {
        CompanyConfiguration::create([
            'company_id' => $companyId,
            'config_type' => 'file_settings',
            'environment' => 'general',
            'service_type' => 'general',
            'config_data' => $archivos,
            'description' => 'Configuraciones de archivos (migradas)',
            'is_active' => true,
        ]);
    }

    private function migrateGenericConfiguration(int $companyId, string $configType, array $configData, string $description): void
    {
        CompanyConfiguration::create([
            'company_id' => $companyId,
            'config_type' => $configType,
            'environment' => 'general',
            'service_type' => 'general',
            'config_data' => $configData,
            'description' => $description,
            'is_active' => true,
        ]);
    }
};