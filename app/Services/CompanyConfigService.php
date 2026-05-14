<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class CompanyConfigService
{
    /**
     * Obtener configuración completa de la empresa con cache
     */
    public function getCompanyConfiguration(Company $company, bool $useCache = true): array
    {
        $cacheKey = "company_config_{$company->id}";
        
        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        // Asegurar que la empresa tenga todas las configuraciones
        if (empty($company->configuraciones)) {
            $company->mergeWithDefaults();
        }
        
        $config = [
            'company_info' => [
                'id' => $company->id,
                'ruc' => $company->ruc,
                'razon_social' => $company->razon_social,
                'nombre_comercial' => $company->nombre_comercial,
                'modo_produccion' => $company->modo_produccion,
                'activo' => $company->activo,
            ],
            'tax_settings' => $company->getTaxSettings(),
            'invoice_settings' => $company->getInvoiceConfig(),
            'gre_settings' => $company->getGuideConfig(),
            'document_settings' => $company->getDocumentConfig(),
        ];
        
        // Cache por 30 minutos
        if ($useCache) {
            Cache::put($cacheKey, $config, 1800);
        }
        
        return $config;
    }

    /**
     * Actualizar configuración específica de la empresa
     */
    public function updateConfiguration(Company $company, string $section, array $data): bool
    {
        try {
            $validSections = [
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
            ];
            
            if (!in_array($section, $validSections)) {
                throw new Exception("Sección de configuración no válida: {$section}");
            }
            
            // Validar datos según sección
            $validatedData = $this->validateConfigurationData($section, $data);
            
            // Actualizar configuración
            $company->setConfig($section, $validatedData, null, 'general', "Configuración de {$section} actualizada");
            
            // Limpiar cache
            $this->clearCompanyCache($company);
            
            Log::info("Configuración actualizada para empresa {$company->ruc}", [
                'section' => $section,
                'company_id' => $company->id,
                'data_keys' => array_keys($validatedData)
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error("Error al actualizar configuración: " . $e->getMessage(), [
                'company_id' => $company->id,
                'section' => $section,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Resetear configuración a valores por defecto
     */
    public function resetToDefaults(Company $company, ?string $section = null): bool
    {
        try {
            $defaults = $company->getDefaultConfigurations();
            
            if ($section) {
                if (!isset($defaults[$section])) {
                    throw new Exception("Sección no existe en configuraciones por defecto: {$section}");
                }
                
                $company->setConfig($section, $defaults[$section], null, 'general', "Sección {$section} reseteada a valores por defecto");
                Log::info("Sección {$section} reseteada a valores por defecto para empresa {$company->ruc}");
            } else {
                $company->update(['configuraciones' => $defaults]);
                Log::info("Todas las configuraciones reseteadas a valores por defecto para empresa {$company->ruc}");
            }
            
            $this->clearCompanyCache($company);
            
            return true;
            
        } catch (Exception $e) {
            Log::error("Error al resetear configuraciones: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validar configuraciones de servicio SUNAT
     */
    public function validateSunatServices(Company $company): array
    {
        $results = [];
        $services = ['facturacion', 'guias_remision', 'resumenes_diarios', 'comunicaciones_baja'];
        
        foreach ($services as $service) {
            $results[$service] = $this->validateService($company, $service);
        }
        
        return $results;
    }

    /**
     * Validar un servicio específico
     */
    private function validateService(Company $company, string $service): array
    {
        try {
            $config = $company->getSunatServiceConfig($service);
            
            $validation = [
                'valid' => false,
                'errors' => [],
                'warnings' => [],
                'config' => $config
            ];
            
            // Validaciones básicas
            if (empty($config['endpoint'])) {
                $validation['errors'][] = "Endpoint no configurado para {$service}";
            }
            
            if ($service === 'guias_remision' && empty($config['api_endpoint'])) {
                $validation['errors'][] = "API endpoint no configurado para guías de remisión";
            }
            
            // Validar timeout
            $timeout = $config['timeout'] ?? 30;
            if ($timeout < 10 || $timeout > 120) {
                $validation['warnings'][] = "Timeout fuera del rango recomendado (10-120 segundos): {$timeout}";
            }
            
            // Validaciones de endpoint
            if (!empty($config['endpoint'])) {
                if (!filter_var($config['endpoint'], FILTER_VALIDATE_URL)) {
                    $validation['errors'][] = "Endpoint no es una URL válida: " . $config['endpoint'];
                } elseif (!str_starts_with($config['endpoint'], 'https://')) {
                    $validation['warnings'][] = "Endpoint no usa HTTPS: " . $config['endpoint'];
                }
            }
            
            // Validar credenciales de empresa
            if (empty($company->ruc)) {
                $validation['errors'][] = "RUC no configurado";
            } elseif (!preg_match('/^\d{11}$/', $company->ruc)) {
                $validation['errors'][] = "RUC no tiene formato válido";
            }
            
            if (empty($company->usuario_sol)) {
                $validation['errors'][] = "Usuario SOL no configurado";
            }
            
            if (empty($company->clave_sol)) {
                $validation['errors'][] = "Clave SOL no configurada";
            }
            
            $validation['valid'] = empty($validation['errors']);
            
            return $validation;
            
        } catch (Exception $e) {
            return [
                'valid' => false,
                'errors' => ["Error al validar servicio: " . $e->getMessage()],
                'warnings' => [],
                'config' => []
            ];
        }
    }

    /**
     * Validar datos de configuración según sección
     */
    private function validateConfigurationData(string $section, array $data): array
    {
        switch ($section) {
            case 'tax_settings':
            case 'invoice_settings':
                return $this->validateInvoiceConfig($data);
                
            case 'gre_settings':
                return $this->validateGuideConfig($data);
                
            case 'service_endpoints':
                return $this->validateSunatServicesConfig($data);
                
            default:
                return $data; // Para otras secciones, devolver tal como está
        }
    }

    /**
     * Validar configuración de facturación
     */
    private function validateInvoiceConfig(array $data): array
    {
        $validated = [];
        
        // IGV porcentaje
        if (isset($data['igv_porcentaje'])) {
            $igv = (float)$data['igv_porcentaje'];
            if ($igv < 0 || $igv > 50) {
                throw new Exception("IGV porcentaje debe estar entre 0 y 50");
            }
            $validated['igv_porcentaje'] = $igv;
        }
        
        // ICBPER monto
        if (isset($data['icbper_monto'])) {
            $icbper = (float)$data['icbper_monto'];
            if ($icbper < 0) {
                throw new Exception("ICBPER monto no puede ser negativo");
            }
            $validated['icbper_monto'] = $icbper;
        }
        
        // Otros campos booleanos
        $booleanFields = [
            'redondeo_automatico',
            'incluir_leyenda_monto',
            'validar_ruc_cliente',
            'permitir_precio_cero'
        ];
        
        foreach ($booleanFields as $field) {
            if (isset($data[$field])) {
                $validated[$field] = (bool)$data[$field];
            }
        }
        
        // Decimales
        $decimalFields = ['decimales_precio_unitario', 'decimales_cantidad'];
        foreach ($decimalFields as $field) {
            if (isset($data[$field])) {
                $decimals = (int)$data[$field];
                if ($decimals < 2 || $decimals > 10) {
                    throw new Exception("{$field} debe estar entre 2 y 10");
                }
                $validated[$field] = $decimals;
            }
        }
        
        return array_merge($data, $validated);
    }

    /**
     * Validar configuración de guías de remisión
     */
    private function validateGuideConfig(array $data): array
    {
        $validated = [];
        
        // Peso default
        if (isset($data['peso_default_kg'])) {
            $peso = (float)$data['peso_default_kg'];
            if ($peso <= 0) {
                throw new Exception("Peso por defecto debe ser mayor a 0");
            }
            $validated['peso_default_kg'] = $peso;
        }
        
        // Bultos default
        if (isset($data['bultos_default'])) {
            $bultos = (int)$data['bultos_default'];
            if ($bultos < 1) {
                throw new Exception("Bultos por defecto debe ser al menos 1");
            }
            $validated['bultos_default'] = $bultos;
        }
        
        return array_merge($data, $validated);
    }

    /**
     * Validar configuración de servicios SUNAT
     */
    private function validateSunatServicesConfig(array $data): array
    {
        // Validar URLs de endpoints
        foreach ($data as $service => $configs) {
            if (is_array($configs)) {
                foreach ($configs as $mode => $config) {
                    if (isset($config['endpoint']) && !filter_var($config['endpoint'], FILTER_VALIDATE_URL)) {
                        throw new Exception("URL inválida para {$service}.{$mode}.endpoint");
                    }
                    
                    if (isset($config['api_endpoint']) && !filter_var($config['api_endpoint'], FILTER_VALIDATE_URL)) {
                        throw new Exception("URL inválida para {$service}.{$mode}.api_endpoint");
                    }
                    
                    if (isset($config['timeout'])) {
                        $timeout = (int)$config['timeout'];
                        if ($timeout < 5 || $timeout > 300) {
                            throw new Exception("Timeout para {$service}.{$mode} debe estar entre 5 y 300 segundos");
                        }
                    }
                }
            }
        }
        
        return $data;
    }

    /**
     * Limpiar cache de configuración de empresa
     */
    public function clearCompanyCache(Company $company): void
    {
        Cache::forget("company_config_{$company->id}");
    }

    /**
     * Migrar empresa existente al nuevo sistema de configuraciones
     */
    public function migrateCompany(Company $company): bool
    {
        try {
            $migrated = $company->migrateToNewConfigStructure();
            
            if ($migrated) {
                $this->clearCompanyCache($company);
                Log::info("Empresa migrada exitosamente al nuevo sistema de configuraciones", [
                    'company_id' => $company->id,
                    'ruc' => $company->ruc
                ]);
            }
            
            return $migrated;
            
        } catch (Exception $e) {
            Log::error("Error al migrar empresa: " . $e->getMessage(), [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Obtener resumen de estado de configuraciones
     */
    public function getConfigurationStatus(Company $company): array
    {
        $validations = $this->validateSunatServices($company);
        
        $status = [
            'overall_valid' => true,
            'services' => $validations,
            'total_errors' => 0,
            'total_warnings' => 0,
            'recommendations' => []
        ];
        
        foreach ($validations as $service => $validation) {
            if (!$validation['valid']) {
                $status['overall_valid'] = false;
            }
            
            $status['total_errors'] += count($validation['errors']);
            $status['total_warnings'] += count($validation['warnings']);
        }
        
        // Generar recomendaciones
        if ($status['total_errors'] > 0) {
            $status['recommendations'][] = "Revisar y corregir errores de configuración antes de usar los servicios";
        }
        
        if ($status['total_warnings'] > 0) {
            $status['recommendations'][] = "Revisar advertencias para optimizar la configuración";
        }
        
        if (!$company->modo_produccion && $status['overall_valid']) {
            $status['recommendations'][] = "Configuración lista para modo BETA. Validar en producción antes del go-live";
        }
        
        return $status;
    }
}