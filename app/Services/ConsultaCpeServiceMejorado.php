<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Boleta;
use App\Models\CreditNote;
use App\Models\DebitNote;
use Greenter\Sunat\ConsultaCpe\Api\AuthApi;
use Greenter\Sunat\ConsultaCpe\Api\ConsultaApi;
use Greenter\Sunat\ConsultaCpe\Configuration;
use Greenter\Sunat\ConsultaCpe\Model\CpeFilter;
use Greenter\Ws\Services\ConsultCdrService;
use Greenter\Ws\Services\SoapClient;
use Greenter\Ws\Services\SunatEndpoints;
use Greenter\Model\Response\StatusCdrResult;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Servicio mejorado de consulta CPE que combina ambos métodos:
 * - API OAuth2 moderna de SUNAT
 * - SOAP tradicional con credenciales SOL
 */
class ConsultaCpeServiceMejorado
{
    protected Company $company;
    protected string $cacheKeyPrefix = 'sunat_token_cpe_';

    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    /**
     * Consultar estado de comprobante electrónico (API Nueva - OAuth2)
     */
    public function consultarComprobante($documento): array
    {
        try {
            // Validar datos del documento
            $errores = $this->validarDatosDocumento($documento);
            if (!empty($errores)) {
                return [
                    'success' => false,
                    'message' => 'Datos de documento inválidos: ' . implode(', ', $errores),
                    'data' => null
                ];
            }

            // Obtener token válido
            $token = $this->obtenerTokenValido();
            
            if (!$token) {
                Log::warning('Token OAuth2 no disponible, usando fallback SOAP', [
                    'company_id' => $this->company->id
                ]);
                return $this->consultarComprobanteSol($documento);
            }

            // Configurar API de consulta
            $config = Configuration::getDefaultConfiguration()
                ->setAccessToken($token)
                ->setHost($this->getApiHost());

            $apiInstance = new ConsultaApi(new Client(), $config);

            // Crear filtro de consulta
            $cpeFilter = $this->crearFiltroCpe($documento);
            
            // Realizar consulta
            $result = $apiInstance->consultarCpe($this->company->ruc, $cpeFilter);

            if (!$result->getSuccess()) {
                Log::warning('Consulta API OAuth2 falló, usando fallback SOAP', [
                    'company_id' => $this->company->id,
                    'api_message' => $result->getMessage()
                ]);
                return $this->consultarComprobanteSol($documento);
            }

            // Procesar respuesta
            $estados = $this->procesarEstadosComprobante($result->getData());
            
            // Actualizar documento con el estado
            $this->actualizarEstadoDocumento($documento, $estados);

            return [
                'success' => true,
                'message' => 'Consulta realizada correctamente',
                'data' => $estados,
                'comprobante_codigo' => "{$documento->serie}-{$documento->correlativo}",
                'metodo' => 'api_oauth2'
            ];

        } catch (Exception $e) {
            Log::error('Error en consulta CPE API: ' . $e->getMessage(), [
                'company_id' => $this->company->id,
                'documento_id' => $documento->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            // Fallback a consulta tradicional si la API OAuth2 falla
            return $this->consultarComprobanteSol($documento);
        }
    }

    /**
     * Consultar estado con credenciales SOL tradicionales (SOAP)
     */
    public function consultarComprobanteSol($documento, bool $incluirCdr = false): array
    {
        try {
            // Validar datos del documento
            $errores = $this->validarDatosDocumento($documento);
            if (!empty($errores)) {
                return [
                    'success' => false,
                    'message' => 'Datos de documento inválidos: ' . implode(', ', $errores),
                    'data' => null
                ];
            }

            // Obtener credenciales SOL
            $credenciales = $this->obtenerCredencialesSol();
            
            if (!$credenciales) {
                return [
                    'success' => false,
                    'message' => 'Credenciales SOL no configuradas para la empresa',
                    'data' => null
                ];
            }

            // Configurar servicio SOAP
            $ws = new SoapClient(SunatEndpoints::FE_CONSULTA_CDR . '?wsdl');
            $ws->setCredentials(
                $credenciales['ruc'] . $credenciales['usuario'],
                $credenciales['clave']
            );

            $service = new ConsultCdrService();
            $service->setClient($ws);

            Log::info('Iniciando consulta SOAP', [
                'company_id' => $this->company->id,
                'documento_id' => $documento->id,
                'ruc' => $this->company->ruc,
                'tipo' => $documento->tipo_documento,
                'serie' => $documento->serie,
                'correlativo' => $documento->correlativo,
                'incluir_cdr' => $incluirCdr
            ]);

            // Realizar consulta
            $result = $incluirCdr 
                ? $service->getStatusCdr(
                    $this->company->ruc,
                    $documento->tipo_documento,
                    $documento->serie,
                    intval($documento->correlativo)
                )
                : $service->getStatus(
                    $this->company->ruc,
                    $documento->tipo_documento,
                    $documento->serie,
                    intval($documento->correlativo)
                );

            if (!$result->isSuccess()) {
                return [
                    'success' => false,
                    'message' => $result->getError() ? $result->getError()->getMessage() : 'Error desconocido en consulta SOAP',
                    'data' => null,
                    'codigo_error' => $result->getCode(),
                    'metodo' => 'soap_sol'
                ];
            }

            // Procesar respuesta SOAP
            $estados = $this->procesarEstadosSoap($result);
            
            // Guardar CDR si está disponible
            $cdrGuardado = null;
            if ($incluirCdr && $result->getCdrZip()) {
                $cdrGuardado = $this->guardarCdr($documento, $result->getCdrZip());
            }
            
            // Actualizar documento
            $this->actualizarEstadoDocumento($documento, $estados);

            return [
                'success' => true,
                'message' => 'Consulta SOL realizada correctamente',
                'data' => $estados,
                'comprobante_codigo' => "{$documento->serie}-{$documento->correlativo}",
                'metodo' => 'soap_sol',
                'cdr_guardado' => $cdrGuardado
            ];

        } catch (Exception $e) {
            Log::error('Error en consulta CPE SOL: ' . $e->getMessage(), [
                'company_id' => $this->company->id,
                'documento_id' => $documento->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error al consultar comprobante: ' . $e->getMessage(),
                'data' => null,
                'metodo' => 'soap_sol'
            ];
        }
    }

    /**
     * Consultar con descarga de CDR
     */
    public function consultarConCdr($documento): array
    {
        return $this->consultarComprobanteSol($documento, true);
    }

    /**
     * Obtener credenciales SOL de la empresa
     */
    protected function obtenerCredencialesSol(): ?array
    {
        $ruc = $this->company->ruc;
        
        if ($this->company->modo_produccion) {
            $usuario = $this->company->usuario_sol_produccion ?? $this->company->usuario_sol;
            $clave = $this->company->clave_sol_produccion ?? $this->company->clave_sol;
        } else {
            $usuario = $this->company->usuario_sol_beta ?? $this->company->usuario_sol;
            $clave = $this->company->clave_sol_beta ?? $this->company->clave_sol;
        }
        
        if (!$usuario || !$clave) {
            Log::warning('Credenciales SOL no configuradas', [
                'company_id' => $this->company->id,
                'ruc' => $ruc,
                'modo_produccion' => $this->company->modo_produccion
            ]);
            return null;
        }
        
        return [
            'ruc' => $ruc,
            'usuario' => $usuario,
            'clave' => $clave
        ];
    }

    /**
     * Procesar estados de respuesta SOAP
     */
    protected function procesarEstadosSoap(StatusCdrResult $result): array
    {
        $estados = [
            'codigo' => $result->getCode(),
            'mensaje' => $result->getMessage(),
            'fecha_consulta' => now()->toISOString(),
            'metodo' => 'soap_sol'
        ];

        if ($result->getCdrResponse()) {
            $cdrResponse = $result->getCdrResponse();
            $estados['cdr_response'] = [
                'codigo' => $cdrResponse->getCode(),
                'descripcion' => $cdrResponse->getDescription(),
                'observaciones' => $cdrResponse->getNotes() ?? []
            ];
        }

        return $estados;
    }

    /**
     * Guardar CDR comprimido
     */
    protected function guardarCdr($documento, string $cdrZip): ?string
    {
        try {
            $filename = "R-{$this->company->ruc}-{$documento->tipo_documento}-{$documento->serie}-{$documento->correlativo}.zip";
            $path = storage_path("app/cdr/{$this->company->ruc}");
            
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }
            
            $fullPath = "{$path}/{$filename}";
            file_put_contents($fullPath, $cdrZip);
            
            Log::info('CDR guardado correctamente', [
                'company_id' => $this->company->id,
                'documento_id' => $documento->id,
                'filename' => $filename,
                'size' => strlen($cdrZip)
            ]);
            
            return $filename;
            
        } catch (Exception $e) {
            Log::error('Error al guardar CDR: ' . $e->getMessage(), [
                'company_id' => $this->company->id,
                'documento_id' => $documento->id
            ]);
            
            return null;
        }
    }

    /**
     * Validar datos de documento para consulta
     */
    public function validarDatosDocumento($documento): array
    {
        $errores = [];
        
        if (empty($documento->serie)) {
            $errores[] = 'Serie es requerida';
        }
        
        if (empty($documento->correlativo)) {
            $errores[] = 'Correlativo es requerido';
        }
        
        if (empty($documento->tipo_documento)) {
            $errores[] = 'Tipo de documento es requerido';
        }
        
        if (empty($documento->fecha_emision)) {
            $errores[] = 'Fecha de emisión es requerida';
        }
        
        if (empty($documento->mto_imp_venta)) {
            $errores[] = 'Monto total es requerido';
        }
        
        return $errores;
    }

    /**
     * Obtener token válido (cache o generar nuevo)
     */
    protected function obtenerTokenValido(): ?string
    {
        $cacheKey = $this->cacheKeyPrefix . $this->company->id;
        
        // Verificar si hay token en cache válido
        $tokenData = Cache::get($cacheKey);
        
        if ($tokenData && $this->esTokenValido($tokenData)) {
            return $tokenData['token'];
        }

        // Generar nuevo token
        return $this->generarNuevoToken();
    }

    /**
     * Verificar si el token aún es válido
     */
    protected function esTokenValido(array $tokenData): bool
    {
        $expiracion = $tokenData['expires_at'] ?? 0;
        return time() < ($expiracion - 300); // 5 minutos de margen
    }

    /**
     * Generar nuevo token de autenticación
     */
    protected function generarNuevoToken(): ?string
    {
        try {
            $authApi = new AuthApi(new Client());
            
            $grantType = 'client_credentials';
            $scope = 'https://api.sunat.gob.pe/v1/contribuyente/contribuyentes';
            $clientId = $this->company->gre_client_id_produccion ?? $this->company->gre_client_id_beta;
            $clientSecret = $this->company->gre_client_secret_produccion ?? $this->company->gre_client_secret_beta;

            if (!$clientId || !$clientSecret) {
                Log::warning('Credenciales OAuth2 no configuradas', [
                    'company_id' => $this->company->id
                ]);
                return null;
            }

            $result = $authApi->getToken($grantType, $scope, $clientId, $clientSecret);
            $token = $result->getAccessToken();

            // Guardar en cache (45 minutos)
            $tokenData = [
                'token' => $token,
                'expires_at' => time() + 2700, // 45 minutos
                'created_at' => time()
            ];

            Cache::put($this->cacheKeyPrefix . $this->company->id, $tokenData, 2700);

            Log::info('Nuevo token CPE generado', [
                'company_id' => $this->company->id,
                'expires_at' => date('Y-m-d H:i:s', $tokenData['expires_at'])
            ]);

            return $token;

        } catch (Exception $e) {
            Log::error('Error al generar token CPE: ' . $e->getMessage(), [
                'company_id' => $this->company->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }

    /**
     * Crear filtro de consulta CPE
     */
    protected function crearFiltroCpe($documento): CpeFilter
    {
        $fechaEmision = date('d/m/Y', strtotime($documento->fecha_emision));
        $monto = round(floatval($documento->mto_imp_venta), 2);

        return (new CpeFilter())
            ->setNumRuc($this->company->ruc)
            ->setCodComp($documento->tipo_documento)
            ->setNumeroSerie($documento->serie)
            ->setNumero(intval($documento->correlativo))
            ->setFechaEmision($fechaEmision)
            ->setMonto($monto);
    }

    /**
     * Procesar estados del comprobante (API OAuth2)
     */
    protected function procesarEstadosComprobante($data): array
    {
        $estados = [
            'fecha_consulta' => now()->toISOString(),
            'metodo' => 'api_oauth2'
        ];

        if (isset($data['estadoCp'])) {
            $estados['estadocpe'] = $data['estadoCp'];
        }

        if (isset($data['estadoRuc'])) {
            $estados['estado_ruc'] = $data['estadoRuc'];
        }

        if (isset($data['condDomiRuc'])) {
            $estados['condicion_domicilio'] = $data['condDomiRuc'];
        }

        return $estados;
    }

    /**
     * Actualizar documento con estado obtenido
     */
    protected function actualizarEstadoDocumento($documento, array $estados): void
    {
        try {
            $updateData = [
                'estado_sunat' => $estados['codigo'] ?? $estados['estadocpe'] ?? 'CONSULTADO',
                'fecha_ultima_consulta' => now(),
                'respuesta_sunat' => json_encode($estados)
            ];

            $documento->update($updateData);

            Log::info('Estado de documento actualizado', [
                'documento_id' => $documento->id,
                'estado' => $updateData['estado_sunat'],
                'metodo' => $estados['metodo'] ?? 'unknown'
            ]);

        } catch (Exception $e) {
            Log::error('Error al actualizar estado del documento: ' . $e->getMessage(), [
                'documento_id' => $documento->id,
                'estados' => $estados
            ]);
        }
    }

    /**
     * Obtener host de API según modo
     */
    protected function getApiHost(): string
    {
        return $this->company->modo_produccion 
            ? 'https://api.sunat.gob.pe'
            : 'https://api-beta.sunat.gob.pe';
    }

    /**
     * Consulta masiva de documentos
     */
    public function consultarDocumentosMasivo(array $documentos): array
    {
        $resultados = [];
        $exitosos = 0;
        $fallidos = 0;

        foreach ($documentos as $documento) {
            $resultado = $this->consultarComprobante($documento);
            
            $resultados[] = [
                'documento_id' => $documento->id,
                'serie_correlativo' => "{$documento->serie}-{$documento->correlativo}",
                'resultado' => $resultado
            ];

            if ($resultado['success']) {
                $exitosos++;
            } else {
                $fallidos++;
            }

            // Delay entre consultas para evitar rate limiting
            usleep(500000); // 0.5 segundos
        }

        return [
            'total_procesados' => count($documentos),
            'exitosos' => $exitosos,
            'fallidos' => $fallidos,
            'resultados' => $resultados
        ];
    }
}