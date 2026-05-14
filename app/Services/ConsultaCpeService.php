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

class ConsultaCpeService
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
            // Obtener token válido
            $token = $this->obtenerTokenValido();
            
            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'No se pudo obtener token de autenticación',
                    'data' => null
                ];
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
                return [
                    'success' => false,
                    'message' => $result->getMessage(),
                    'data' => null
                ];
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
            // Obtener credenciales SOL
            $credenciales = $this->obtenerCredencialesSol();
            
            if (!$credenciales) {
                return [
                    'success' => false,
                    'message' => 'Credenciales SOL no configuradas',
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
                    'message' => $result->getError()->getMessage(),
                    'data' => null,
                    'codigo_error' => $result->getCode()
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
                'data' => null
            ];
        }
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
                throw new Exception('Credenciales de API SUNAT no configuradas para la empresa');
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
     * Procesar estados del comprobante
     */
    protected function procesarEstadosComprobante($data): array
    {
        $estados = [];

        if (isset($data['estadoCp'])) {
            $estados['estadocpe'] = $data['estadoCp'];
        }

        if (isset($data['estadoRuc'])) {
            $estados['estado_ruc'] = $data['estadoRuc'];
        }

        if (isset($data['condDomiRuc'])) {
            $estados['condicion_domicilio'] = $data['condDomiRuc'];
        }

        if (isset($data['ubigeo'])) {
            $estados['ubigeo'] = $data['ubigeo'];
        }

        // Interpretación de estados
        $estados['descripcion_estado'] = $this->interpretarEstadoCpe($estados['estadocpe'] ?? null);
        $estados['consulta_fecha'] = date('Y-m-d H:i:s');

        return $estados;
    }

    /**
     * Interpretar código de estado CPE
     */
    protected function interpretarEstadoCpe(?string $estadoCpe): string
    {
        $estados = [
            '1' => 'Aceptado',
            '2' => 'Anulado',
            '3' => 'Autorizado',
            '0' => 'No existe',
            '-1' => 'Error en consulta'
        ];

        return $estados[$estadoCpe] ?? 'Estado desconocido';
    }

    /**
     * Actualizar estado en el documento
     */
    protected function actualizarEstadoDocumento($documento, array $estados): void
    {
        $documento->update([
            'consulta_cpe_estado' => $estados['estadocpe'] ?? null,
            'consulta_cpe_respuesta' => json_encode($estados),
            'consulta_cpe_fecha' => now(),
            'estado_sunat' => $estados['estadocpe'] ?? $documento->estado_sunat
        ]);

        Log::info('Estado de documento actualizado', [
            'documento_type' => get_class($documento),
            'documento_id' => $documento->id,
            'nuevo_estado' => $estados['estadocpe'] ?? null
        ]);
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