<?php

namespace App\Traits;

trait ConfigurableCompany
{
    /**
     * Estructura por defecto de configuraciones
     */
    public function getDefaultConfigurations(): array
    {
        return [
            'servicios_sunat' => [
                'facturacion' => [
                    'beta' => [
                        'endpoint' => 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService',
                        'wsdl' => 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService?wsdl',
                        'timeout' => 30,
                    ],
                    'produccion' => [
                        'endpoint' => 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService',
                        'wsdl' => 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService?wsdl',
                        'timeout' => 45,
                    ]
                ],
                'guias_remision' => [
                    'beta' => [
                        'endpoint' => 'https://gre-test.nubefact.com/v1',
                        'api_endpoint' => 'https://api-cpe-beta.sunat.gob.pe/v1/',
                        'wsdl' => 'https://e-beta.sunat.gob.pe/ol-ti-itcpgre-beta/billService?wsdl',
                        'timeout' => 30,
                    ],
                    'produccion' => [
                        'endpoint' => 'https://api-cpe.sunat.gob.pe/v1/',
                        'api_endpoint' => 'https://api-cpe.sunat.gob.pe/v1/',
                        'wsdl' => 'https://e-guiaremision.sunat.gob.pe/ol-ti-itemision-guia-gem/billService?wsdl',
                        'timeout' => 45,
                    ]
                ],
                'resumenes_diarios' => [
                    'beta' => [
                        'endpoint' => 'https://e-beta.sunat.gob.pe/ol-ti-itemision-otroscpe-gem-beta/billService',
                        'wsdl' => 'https://e-beta.sunat.gob.pe/ol-ti-itemision-otroscpe-gem-beta/billService?wsdl',
                        'timeout' => 60,
                    ],
                    'produccion' => [
                        'endpoint' => 'https://e-factura.sunat.gob.pe/ol-ti-itemision-otroscpe-gem/billService',
                        'wsdl' => 'https://e-factura.sunat.gob.pe/ol-ti-itemision-otroscpe-gem/billService?wsdl',
                        'timeout' => 60,
                    ]
                ],
                'comunicaciones_baja' => [
                    'beta' => [
                        'endpoint' => 'https://e-beta.sunat.gob.pe/ol-ti-itemision-otroscpe-gem-beta/billService',
                        'wsdl' => 'https://e-beta.sunat.gob.pe/ol-ti-itemision-otroscpe-gem-beta/billService?wsdl',
                        'timeout' => 60,
                    ],
                    'produccion' => [
                        'endpoint' => 'https://e-factura.sunat.gob.pe/ol-ti-itemision-otroscpe-gem/billService',
                        'wsdl' => 'https://e-factura.sunat.gob.pe/ol-ti-itemision-otroscpe-gem/billService?wsdl',
                        'timeout' => 60,
                    ]
                ]
            ],
            
            'credenciales_gre' => [
                'beta' => [
                    'client_id' => 'test-85e5b0ae-255c-4891-a595-0b98c65c9854',
                    'client_secret' => 'test-Hty/M6QshYvPgItX2P0+Kw==',
                    'ruc_proveedor' => '20161515648',
                    'usuario_sol' => 'MODDATOS',
                    'clave_sol' => 'MODDATOS',
                ],
                'produccion' => [
                    'client_id' => null,
                    'client_secret' => null,
                    'ruc_proveedor' => null,
                    'usuario_sol' => null,
                    'clave_sol' => null,
                ]
            ],
            
            'facturacion' => [
                'igv_porcentaje' => 18.00,
                'isc_porcentaje' => 0.00,
                'icbper_monto' => 0.50,
                'ivap_porcentaje' => 4.00,
                'redondeo_automatico' => true,
                'decimales_precio_unitario' => 10,
                'decimales_cantidad' => 10,
                'incluir_leyenda_monto' => true,
                'validar_ruc_cliente' => false,
                'permitir_precio_cero' => false,
            ],
            
            'guias_remision' => [
                'peso_default_kg' => 1.000,
                'bultos_default' => 1,
                'verificacion_automatica' => true,
                'intentos_maximos_verificacion' => 5,
                'intervalo_verificacion_segundos' => 30,
                'timeout_consulta_segundos' => 300,
                'reintento_envio_fallido' => 3,
                'modalidad_transporte_default' => '02', // Transporte privado
                'motivo_traslado_default' => '01', // Venta
            ],
            
            'resumenes_diarios' => [
                'generar_automatico' => false,
                'hora_generacion' => '23:59',
                'verificar_estado_automatico' => true,
                'intentos_maximos_verificacion' => 10,
                'intervalo_verificacion_minutos' => 5,
                'incluir_solo_pendientes' => true,
                'agrupar_por_fecha' => true,
            ],
            
            'comunicaciones_baja' => [
                'motivo_default' => '01', // Error en el RUC
                'generar_automatico_rechazo' => false,
                'verificar_estado_automatico' => true,
                'permitir_anulacion_mismo_dia' => true,
            ],
            
            'archivos' => [
                'conservar_xml' => true,
                'conservar_cdr' => true,
                'conservar_pdf' => true,
                'ruta_base' => 'sunat/{company_id}/{year}/{month}',
                'estructura_carpetas' => '{tipo_documento}/{serie}',
                'dias_conservar_archivos' => 2555, // 7 años aprox
                'comprimir_archivos_antiguos' => false,
                'backup_automatico' => false,
            ],
            
            'documentos' => [
                'generar_xml_automatico' => true,
                'generar_pdf_automatico' => false,
                'enviar_sunat_automatico' => false,
                'formato_pdf_default' => 'a4',
                'orientacion_pdf_default' => 'portrait',
                'incluir_qr_pdf' => true,
                'incluir_hash_pdf' => true,
                'marca_agua_pdf' => null,
                'logo_en_pdf' => true,
            ],
            
            'notificaciones' => [
                'email_envio_exitoso' => false,
                'email_envio_fallido' => true,
                'email_destinatario' => null,
                'email_copia_oculta' => null,
                'webhook_url' => null,
                'webhook_eventos' => ['enviado', 'aceptado', 'rechazado'],
                'webhook_token' => null,
            ],
            
            'seguridad' => [
                'log_transacciones' => true,
                'log_detallado' => false,
                'verificar_certificado_vigencia' => true,
                'dias_alerta_vencimiento_cert' => 30,
                'backup_certificado' => true,
                'encriptar_credenciales' => true,
            ],
        ];
    }

    /**
     * Obtener configuración específica con soporte para claves anidadas
     */
    public function getConfig(string $key, $default = null)
    {
        $configs = $this->configuraciones ?? [];
        
        // Fusionar con defaults si está vacío
        if (empty($configs)) {
            $this->mergeWithDefaults();
            $configs = $this->configuraciones;
        }
        
        // Si es una clave anidada como 'facturacion.igv_porcentaje'
        if (str_contains($key, '.')) {
            $value = data_get($configs, $key);
            
            // Si no existe, obtener de configuraciones por defecto
            if ($value === null) {
                $defaultConfigs = $this->getDefaultConfigurations();
                return data_get($defaultConfigs, $key, $default);
            }
            
            return $value;
        }
        
        return $configs[$key] ?? $this->getDefaultConfigurations()[$key] ?? $default;
    }

    /**
     * Establecer configuración específica
     */
    public function setConfig(string $key, $value): bool
    {
        $configs = $this->configuraciones ?? [];
        
        if (str_contains($key, '.')) {
            data_set($configs, $key, $value);
        } else {
            $configs[$key] = $value;
        }
        
        return $this->update(['configuraciones' => $configs]);
    }

    /**
     * Fusionar configuraciones con las por defecto
     */
    public function mergeWithDefaults(): void
    {
        $defaults = $this->getDefaultConfigurations();
        $current = $this->configuraciones ?? [];
        
        $this->configuraciones = array_merge_recursive($defaults, $current);
    }

    // ==================== MÉTODOS ESPECÍFICOS PARA SERVICIOS SUNAT ====================

    /**
     * Obtener endpoint para servicio específico según modo
     */
    public function getSunatEndpoint(string $service, string $type = 'endpoint'): string
    {
        $mode = $this->modo_produccion ? 'produccion' : 'beta';
        return $this->getConfig("servicios_sunat.{$service}.{$mode}.{$type}");
    }

    /**
     * Obtener configuración completa de servicio SUNAT
     */
    public function getSunatServiceConfig(string $service): array
    {
        $mode = $this->modo_produccion ? 'produccion' : 'beta';
        return $this->getConfig("servicios_sunat.{$service}.{$mode}", []);
    }

    /**
     * Obtener endpoint para facturas según modo
     */
    public function getInvoiceEndpoint(): string
    {
        return $this->getSunatEndpoint('facturacion', 'endpoint');
    }

    /**
     * Obtener WSDL para facturas según modo
     */
    public function getInvoiceWsdl(): string
    {
        return $this->getSunatEndpoint('facturacion', 'wsdl');
    }

    /**
     * Obtener endpoint para guías de remisión según modo
     */
    public function getGuideEndpoint(): string
    {
        return $this->getSunatEndpoint('guias_remision', 'endpoint');
    }

    /**
     * Obtener API endpoint para guías de remisión según modo
     */
    public function getGuideApiEndpoint(): string
    {
        return $this->getSunatEndpoint('guias_remision', 'api_endpoint');
    }

    /**
     * Obtener WSDL para guías de remisión según modo
     */
    public function getGuideWsdl(): string
    {
        return $this->getSunatEndpoint('guias_remision', 'wsdl');
    }

    /**
     * Obtener endpoint para resúmenes diarios según modo
     */
    public function getSummaryEndpoint(): string
    {
        return $this->getSunatEndpoint('resumenes_diarios', 'endpoint');
    }

    /**
     * Obtener endpoint para comunicaciones de baja según modo
     */
    public function getVoidedEndpoint(): string
    {
        return $this->getSunatEndpoint('comunicaciones_baja', 'endpoint');
    }

    // ==================== MÉTODOS DE CONFIGURACIÓN ESPECÍFICA ====================

    /**
     * Obtener configuraciones de facturación
     */
    public function getInvoiceConfig(): array
    {
        return $this->getConfig('facturacion', []);
    }

    /**
     * Obtener configuraciones de guías de remisión
     */
    public function getGuideConfig(): array
    {
        return $this->getConfig('guias_remision', []);
    }

    /**
     * Obtener configuraciones de documentos
     */
    public function getDocumentConfig(): array
    {
        return $this->getConfig('documentos', []);
    }

    /**
     * Obtener configuraciones de archivos
     */
    public function getFileConfig(): array
    {
        return $this->getConfig('archivos', []);
    }

    // ==================== MÉTODOS DE VALIDACIÓN Y AYUDA ====================

    /**
     * Verificar si debe generar PDF automáticamente
     */
    public function shouldGeneratePdfAutomatically(): bool
    {
        return $this->getConfig('documentos.generar_pdf_automatico', false);
    }

    /**
     * Verificar si debe enviar a SUNAT automáticamente
     */
    public function shouldSendToSunatAutomatically(): bool
    {
        return $this->getConfig('documentos.enviar_sunat_automatico', false);
    }

    /**
     * Obtener porcentaje de IGV
     */
    public function getIgvPercentage(): float
    {
        return $this->getConfig('facturacion.igv_porcentaje', 18.00);
    }

    /**
     * Obtener monto del ICBPER
     */
    public function getIcbperAmount(): float
    {
        return $this->getConfig('facturacion.icbper_monto', 0.50);
    }

    /**
     * Obtener porcentaje de IVAP
     */
    public function getIvapPercentage(): float
    {
        return $this->getConfig('facturacion.ivap_porcentaje', 4.00);
    }

    /**
     * Verificar si debe redondear automáticamente
     */
    public function shouldRoundAutomatically(): bool
    {
        return $this->getConfig('facturacion.redondeo_automatico', true);
    }

    /**
     * Obtener timeout para servicio específico
     */
    public function getServiceTimeout(string $service): int
    {
        $mode = $this->modo_produccion ? 'produccion' : 'beta';
        return $this->getConfig("servicios_sunat.{$service}.{$mode}.timeout", 30);
    }

    /**
     * Obtener configuración de verificación automática para GRE
     */
    public function shouldVerifyGuideAutomatically(): bool
    {
        return $this->getConfig('guias_remision.verificacion_automatica', true);
    }

    // ==================== MÉTODOS ESPECÍFICOS PARA CREDENCIALES GRE ====================

    /**
     * Obtener credenciales GRE para el ambiente actual
     */
    public function getGreCredentials(): array
    {
        $mode = $this->modo_produccion ? 'produccion' : 'beta';
        return $this->getConfig("credenciales_gre.{$mode}", []);
    }

    /**
     * Obtener Client ID para GRE
     */
    public function getGreClientId(): ?string
    {
        $mode = $this->modo_produccion ? 'produccion' : 'beta';
        return $this->getConfig("credenciales_gre.{$mode}.client_id");
    }

    /**
     * Obtener Client Secret para GRE
     */
    public function getGreClientSecret(): ?string
    {
        $mode = $this->modo_produccion ? 'produccion' : 'beta';
        return $this->getConfig("credenciales_gre.{$mode}.client_secret");
    }

    /**
     * Obtener RUC del proveedor GRE
     */
    public function getGreRucProveedor(): ?string
    {
        $mode = $this->modo_produccion ? 'produccion' : 'beta';
        $ruc = $this->getConfig("credenciales_gre.{$mode}.ruc_proveedor");
        
        // Si no está configurado, usar el RUC de la empresa
        return $ruc ?: $this->ruc;
    }

    /**
     * Obtener Usuario SOL para GRE
     */
    public function getGreUsuarioSol(): ?string
    {
        $mode = $this->modo_produccion ? 'produccion' : 'beta';
        $usuario = $this->getConfig("credenciales_gre.{$mode}.usuario_sol");
        
        // Si no está configurado, usar el usuario SOL general
        return $usuario ?: $this->usuario_sol;
    }

    /**
     * Obtener Clave SOL para GRE
     */
    public function getGreClaveSol(): ?string
    {
        $mode = $this->modo_produccion ? 'produccion' : 'beta';
        $clave = $this->getConfig("credenciales_gre.{$mode}.clave_sol");
        
        // Si no está configurado, usar la clave SOL general
        return $clave ?: $this->clave_sol;
    }

    /**
     * Verificar si las credenciales GRE están configuradas
     */
    public function hasGreCredentials(): bool
    {
        $credentials = $this->getGreCredentials();
        
        return !empty($credentials['client_id']) && 
               !empty($credentials['client_secret']) && 
               !empty($this->getGreRucProveedor()) && 
               !empty($this->getGreUsuarioSol()) && 
               !empty($this->getGreClaveSol());
    }

    /**
     * Configurar credenciales GRE para un ambiente específico
     */
    public function setGreCredentials(string $mode, array $credentials): bool
    {
        if (!in_array($mode, ['beta', 'produccion'])) {
            throw new \InvalidArgumentException("Modo debe ser 'beta' o 'produccion'");
        }

        $validKeys = ['client_id', 'client_secret', 'ruc_proveedor', 'usuario_sol', 'clave_sol'];
        
        foreach ($credentials as $key => $value) {
            if (!in_array($key, $validKeys)) {
                throw new \InvalidArgumentException("Clave inválida: {$key}");
            }
            
            $this->setConfig("credenciales_gre.{$mode}.{$key}", $value);
        }

        return true;
    }

    /**
     * Obtener configuración completa formateada para logging
     */
    public function getConfigSummary(): array
    {
        $mode = $this->modo_produccion ? 'PRODUCCIÓN' : 'BETA';
        
        return [
            'modo' => $mode,
            'facturacion_endpoint' => $this->getInvoiceEndpoint(),
            'guias_endpoint' => $this->getGuideEndpoint(),
            'guias_api_endpoint' => $this->getGuideApiEndpoint(),
            'resumenes_endpoint' => $this->getSummaryEndpoint(),
            'igv_porcentaje' => $this->getIgvPercentage(),
            'generar_pdf_auto' => $this->shouldGeneratePdfAutomatically(),
            'enviar_sunat_auto' => $this->shouldSendToSunatAutomatically(),
            'verificar_guias_auto' => $this->shouldVerifyGuideAutomatically(),
            'credenciales_gre_configuradas' => $this->hasGreCredentials(),
            'gre_client_id' => $this->getGreClientId() ? '***' . substr($this->getGreClientId(), -4) : 'No configurado',
        ];
    }
}