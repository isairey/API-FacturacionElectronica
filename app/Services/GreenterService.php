<?php

namespace App\Services;

use App\Models\Company;
use Greenter\See;
use Greenter\Model\Company\Company as GreenterCompany;
use Greenter\Model\Company\Address;
use Greenter\Model\Client\Client as GreenterClient;
use Greenter\Model\Sale\Invoice as GreenterInvoice;
use Greenter\Model\Sale\Note as GreenterNote;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\FormaPagos\FormaPagoCredito;
use Greenter\Model\Sale\Cuota;
use Greenter\Model\Summary\Summary;
use Greenter\Model\Summary\SummaryDetail;
use Greenter\Model\Summary\SummaryPerception;
use Greenter\Model\Despatch\Despatch;
use Greenter\Model\Despatch\DespatchDetail;
use Greenter\Model\Despatch\Direction;
use Greenter\Model\Despatch\Shipment;
use Greenter\Model\Despatch\Transportist;
use Greenter\Model\Despatch\Driver;
use Greenter\Model\Despatch\Vehicle;
use Greenter\Model\Retention\Retention;
use Greenter\Model\Retention\RetentionDetail;
use Greenter\Model\Retention\Payment;
use Greenter\Model\Retention\Exchange;
use Greenter\Model\Voided\Voided;
use Greenter\Model\Voided\VoidedDetail;
use Greenter\Ws\Services\SunatEndpoints;
use Illuminate\Support\Facades\Log;
use Exception;

class GreenterService
{
    protected $see;
    protected $seeApi;
    protected $company;

    public function __construct(Company $company)
    {
        $this->company = $company;
        $this->see = $this->initializeSee();
        $this->seeApi = $this->initializeSeeApi();
    }

    protected function initializeSee(): See
    {
        $see = new See();
        
        // Usar configuraciones de la base de datos
        $endpoint = $this->company->getInvoiceEndpoint();
        
        $see->setService($endpoint);
        
        // Configurar certificado cargando desde archivo
        try {
            $certificadoPath = storage_path('app/public/certificado/certificado.pem');
            
            if (!file_exists($certificadoPath)) {
                throw new Exception("Archivo de certificado no encontrado: " . $certificadoPath);
            }
            
            $certificadoContent = file_get_contents($certificadoPath);
            
            if ($certificadoContent === false) {
                throw new Exception("No se pudo leer el archivo de certificado");
            }
            
            $see->setCertificate($certificadoContent);
            Log::info("Certificado cargado desde archivo: " . $certificadoPath);
        } catch (Exception $e) {
            Log::error("Error al configurar certificado: " . $e->getMessage());
            throw new Exception("Error al configurar certificado: " . $e->getMessage());
        }
        
        // Configurar credenciales SOL
        $see->setClaveSOL(
            $this->company->ruc,
            $this->company->usuario_sol,
            $this->company->clave_sol
        );
        
        // Configurar cache
        $cachePath = storage_path('app/greenter/cache');
        if (!file_exists($cachePath)) {
            mkdir($cachePath, 0755, true);
        }
        $see->setCachePath($cachePath);

        return $see;
    }

    /**
     * Inicializar API específica para guías de remisión
     */
    protected function initializeSeeApi()
    {
        // Usar configuraciones de la base de datos para GRE
        $guideConfig = $this->company->getSunatServiceConfig('guias_remision');
        $endpoint = $guideConfig['api_endpoint'] ?? $this->company->getGuideApiEndpoint();
        
        $api = new \Greenter\Api([
            'auth' => $endpoint,
            'cpe' => $endpoint,
        ]);
        
        // Configurar certificado
        try {
            $certificadoPath = storage_path('app/public/certificado/certificado.pem');
            
            if (!file_exists($certificadoPath)) {
                throw new Exception("Archivo de certificado no encontrado para GRE: " . $certificadoPath);
            }
            
            $certificadoContent = file_get_contents($certificadoPath);
            
            if ($certificadoContent === false) {
                throw new Exception("No se pudo leer el archivo de certificado para GRE");
            }
            
            $api->setCertificate($certificadoContent);
            Log::info("Certificado GRE cargado desde archivo: " . $certificadoPath);
        } catch (Exception $e) {
            Log::error("Error al configurar certificado para GRE: " . $e->getMessage());
            throw new Exception("Error al configurar certificado para GRE: " . $e->getMessage());
        }
        
        // Configurar credenciales SOL para API GRE
        try {
            $solUser = $this->company->ruc . $this->company->usuario_sol;
            
            // Verificar si el método setCredentials existe
            if (method_exists($api, 'setCredentials')) {
                $api->setCredentials($solUser, $this->company->clave_sol);
            } else {
                // Método alternativo para configurar credenciales
                $api->setClaveSOL($this->company->ruc, $this->company->usuario_sol, $this->company->clave_sol);
            }
            
        } catch (Exception $e) {
            Log::warning("No se pudieron configurar credenciales para GRE API: " . $e->getMessage());
            // Continúa sin error fatal para permitir facturas normales
        }
        
        Log::info("API de GRE configurada", [
            'endpoint' => $endpoint,
            'modo' => $this->company->modo_produccion ? 'PRODUCCIÓN' : 'BETA',
            'ruc' => $this->company->ruc
        ]);
        
        return $api;
    }

    public function getGreenterCompany(): GreenterCompany
    {
        $company = new GreenterCompany();
        $company->setRuc($this->company->ruc)
                ->setRazonSocial($this->company->razon_social)
                ->setNombreComercial($this->company->nombre_comercial);

        $address = new Address();
        $address->setUbigueo($this->company->ubigeo)
                ->setDepartamento($this->company->departamento)
                ->setProvincia($this->company->provincia)
                ->setDistrito($this->company->distrito)
                ->setUrbanizacion('-')
                ->setDireccion($this->company->direccion)
                ->setCodLocal('0000');

        $company->setAddress($address);

        return $company;
    }

    public function getGreenterClient($clientData): GreenterClient
    {
        Log::info("=== INICIO getGreenterClient ===", [
            'clientData_keys' => is_array($clientData) ? array_keys($clientData) : 'NOT_ARRAY',
            'clientData' => $clientData
        ]);

        $client = new GreenterClient();
        $client->setTipoDoc($clientData['tipo_documento'])
               ->setNumDoc($clientData['numero_documento'])
               ->setRznSocial($clientData['razon_social']);

        if (isset($clientData['direccion'])) {
            $address = new Address();
            $address->setDireccion($clientData['direccion']);
            if (isset($clientData['ubigeo'])) {
                $address->setUbigueo($clientData['ubigeo']);
            }
            $client->setAddress($address);
        }

        return $client;
    }

    public function createInvoice(array $invoiceData): GreenterInvoice
    {
        
        $invoice = new GreenterInvoice();
        
        // Configuración básica
        $invoice->setUblVersion($invoiceData['ubl_version'] ?? '2.1')
                ->setTipoOperacion($invoiceData['tipo_operacion'] ?? '0101')
                ->setTipoDoc($invoiceData['tipo_documento'])
                ->setSerie($invoiceData['serie'])
                ->setCorrelativo($invoiceData['correlativo'])
                ->setFechaEmision(new \DateTime($invoiceData['fecha_emision']))
                ->setTipoMoneda($invoiceData['moneda'] ?? 'PEN');

        // Fecha de vencimiento (opcional)
        if (isset($invoiceData['fecha_vencimiento'])) {
            $invoice->setFecVencimiento(new \DateTime($invoiceData['fecha_vencimiento']));
        }

        // Forma de pago
        $formaPago = $this->getFormaPago($invoiceData);
        $invoice->setFormaPago($formaPago);

        // Cuotas (solo para crédito)
        if (($invoiceData['forma_pago_tipo'] ?? 'Contado') === 'Credito' && isset($invoiceData['forma_pago_cuotas'])) {
            $cuotas = [];
            foreach ($invoiceData['forma_pago_cuotas'] as $cuotaData) {
                $cuota = new Cuota();
                $cuota->setMoneda($cuotaData['moneda'] ?? 'PEN')
                      ->setMonto($cuotaData['monto'])
                      ->setFechaPago(new \DateTime($cuotaData['fecha_pago']));
                $cuotas[] = $cuota;
            }
            $invoice->setCuotas($cuotas);
        }

        // Detracción
        if (isset($invoiceData['detraccion']) && !empty($invoiceData['detraccion'])) {
            $detraccionData = $invoiceData['detraccion'];
            $detraccion = new \Greenter\Model\Sale\Detraction();
            
            $detraccion->setCodBienDetraccion($detraccionData['codigo_bien_servicio'])
                      ->setCodMedioPago($detraccionData['codigo_medio_pago'] ?? '001')
                      ->setCtaBanco($detraccionData['cuenta_banco'] ?? '')
                      ->setPercent($detraccionData['porcentaje']);

            // Calcular monto de detracción
            if (isset($detraccionData['monto'])) {
                $detraccion->setMount($detraccionData['monto']);
            }

            $invoice->setDetraccion($detraccion);
        }

        // Percepción
        if (isset($invoiceData['percepcion']) && !empty($invoiceData['percepcion'])) {
            $percepcionData = $invoiceData['percepcion'];
            $percepcion = new \Greenter\Model\Sale\SalePerception();
            
            $percepcion->setCodReg($percepcionData['cod_regimen'])
                      ->setPorcentaje($percepcionData['tasa'] / 100) // Convertir porcentaje a decimal
                      ->setMtoBase($percepcionData['monto_base'])
                      ->setMto($percepcionData['monto'])
                      ->setMtoTotal($percepcionData['monto_total']);

            $invoice->setPerception($percepcion);
        }

        // Retención
        if (isset($invoiceData['retencion']) && !empty($invoiceData['retencion'])) {
            $retencionData = $invoiceData['retencion'];
            $retencion = new \Greenter\Model\Sale\Charge();
            
            $retencion->setCodTipo('62') // Código para retenciones (catálogo 53)
                      ->setMontoBase($retencionData['monto_base'])
                      ->setFactor($retencionData['tasa'] / 100) // Convertir porcentaje a decimal
                      ->setMonto($retencionData['monto']);

            $invoice->setDescuentos([$retencion]);
        }

        // Empresa y cliente
        $invoice->setCompany($this->getGreenterCompany())
                ->setClient($this->getGreenterClient($invoiceData['client']));

        // Montos - Manejo especial para exportaciones
        $tipoOperacion = $invoiceData['tipo_operacion'] ?? '0101';
        
        if ($tipoOperacion === '0200') {
            // Exportación - usar setMtoOperExportacion y NO establecer otros montos
            $invoice->setMtoOperExportacion($invoiceData['valor_venta'])
                    ->setMtoIGV(0)
                    ->setMtoISC($invoiceData['mto_isc'] ?? 0)
                    ->setMtoOtrosTributos($invoiceData['mto_otros_tributos'] ?? 0)
                    ->setTotalImpuestos($invoiceData['mto_isc'] ?? 0)
                    ->setValorVenta($invoiceData['valor_venta'])
                    ->setSubTotal($invoiceData['valor_venta'])
                    ->setMtoImpVenta($invoiceData['valor_venta']);
            
            // NO establecer monto para operaciones gravadas, exoneradas, inafectas para exportación
            // Solo exportación y gratuitas si aplican
            if (isset($invoiceData['mto_oper_gratuitas']) && $invoiceData['mto_oper_gratuitas'] > 0) {
                $invoice->setMtoOperGratuitas($invoiceData['mto_oper_gratuitas']);
                
            }
        } else {
            // Operaciones normales
            $invoice->setMtoOperGravadas($invoiceData['mto_oper_gravadas'])
                    ->setMtoOperExoneradas($invoiceData['mto_oper_exoneradas'])
                    ->setMtoOperInafectas($invoiceData['mto_oper_inafectas'])
                    ->setMtoOperGratuitas($invoiceData['mto_oper_gratuitas'])
                    ->setMtoIGVGratuitas($invoiceData['mto_igv_gratuitas'] ?? 0)
                    ->setMtoIGV($invoiceData['mto_igv'])
                    ->setMtoISC($invoiceData['mto_isc'] ?? 0)
                    ->setMtoOtrosTributos($invoiceData['mto_otros_tributos'] ?? 0)
                    ->setTotalImpuestos($invoiceData['total_impuestos'])
                    ->setValorVenta($invoiceData['valor_venta'])
                    ->setSubTotal($invoiceData['sub_total'])
                    ->setMtoImpVenta($invoiceData['mto_imp_venta']);
        }

        // ICBPER
        if (isset($invoiceData['mto_icbper']) && $invoiceData['mto_icbper'] > 0) {
            $invoice->setIcbper($invoiceData['mto_icbper']);
        }

        // IVAP (Impuesto a la Venta del Arroz Pilado)
        if (isset($invoiceData['mto_base_ivap']) && $invoiceData['mto_base_ivap'] > 0) {
            $invoice->setMtoBaseIvap($invoiceData['mto_base_ivap'])
                    ->setMtoIvap($invoiceData['mto_ivap'] ?? 0);
        }

        // IGV Gratuitas (solo para operaciones gratuitas)
        if (isset($invoiceData['mto_igv_gratuitas']) && $invoiceData['mto_igv_gratuitas'] > 0) {
            $invoice->setMtoIGVGratuitas($invoiceData['mto_igv_gratuitas']);
        }

        // Anticipos
        if (isset($invoiceData['mto_anticipos']) && $invoiceData['mto_anticipos'] > 0) {
            $invoice->setMtoAnticipo($invoiceData['mto_anticipos']);
        }

        // Detalles
        $details = $this->createSaleDetails($invoiceData['detalles']);
        $invoice->setDetails($details);

        // Leyendas
        if (isset($invoiceData['leyendas']) && !empty($invoiceData['leyendas'])) {
            $legends = $this->createLegends($invoiceData['leyendas']);
            $invoice->setLegends($legends);
        }

        return $invoice;
    }

    public function createNote(array $noteData): GreenterNote
    {
        $note = new GreenterNote();
        
        // Configuración básica
        $note->setUblVersion($noteData['ubl_version'] ?? '2.1')
             ->setTipoDoc($noteData['tipo_documento'])
             ->setSerie($noteData['serie'])
             ->setCorrelativo($noteData['correlativo'])
             ->setFechaEmision(new \DateTime($noteData['fecha_emision']))
             ->setTipDocAfectado($noteData['tipo_doc_afectado'])
             ->setNumDocfectado($noteData['num_doc_afectado'])
             ->setCodMotivo($noteData['cod_motivo'])
             ->setDesMotivo($noteData['des_motivo'])
             ->setTipoMoneda($noteData['moneda'] ?? 'PEN');

        // Empresa y cliente
        $note->setCompany($this->getGreenterCompany())
             ->setClient($this->getGreenterClient($noteData['client']));

        // Forma de pago (solo si es a crédito)
        if (isset($noteData['forma_pago_tipo']) && $noteData['forma_pago_tipo'] === 'Credito') {
            $note->setFormaPago(new FormaPagoCredito($noteData['mto_imp_venta']));
            
            // Cuotas (si están definidas)
            if (isset($noteData['forma_pago_cuotas']) && !empty($noteData['forma_pago_cuotas'])) {
                $cuotas = [];
                foreach ($noteData['forma_pago_cuotas'] as $cuotaData) {
                    $cuota = new Cuota();
                    $cuota->setMonto($cuotaData['monto'])
                          ->setFechaPago(new \DateTime($cuotaData['fecha_pago']));
                    $cuotas[] = $cuota;
                }
                $note->setCuotas($cuotas);
            }
        }
        // NOTA: Para pagos al contado, NO se establece forma de pago según ejemplo de Greenter

        // Guías relacionadas (opcional)
        if (isset($noteData['guias']) && !empty($noteData['guias'])) {
            $guias = $this->createDocuments($noteData['guias']);
            $note->setGuias($guias);
        }

        // Montos
        $note->setMtoOperGravadas($noteData['mto_oper_gravadas'])
             ->setMtoOperExoneradas($noteData['mto_oper_exoneradas'])
             ->setMtoOperInafectas($noteData['mto_oper_inafectas'])
             ->setMtoIGV($noteData['mto_igv'])
             ->setTotalImpuestos($noteData['total_impuestos'])
             ->setMtoImpVenta($noteData['mto_imp_venta']);

        // Montos opcionales
        if (isset($noteData['mto_oper_gratuitas']) && $noteData['mto_oper_gratuitas'] > 0) {
            $note->setMtoOperGratuitas($noteData['mto_oper_gratuitas']);
        }
        
        if (isset($noteData['mto_isc']) && $noteData['mto_isc'] > 0) {
            $note->setMtoISC($noteData['mto_isc']);
        }
        
        if (isset($noteData['mto_icbper']) && $noteData['mto_icbper'] > 0) {
            $note->setMtoOtrosTributos($noteData['mto_icbper']);
        }

        // Detalles
        $details = $this->createSaleDetails($noteData['detalles']);
        $note->setDetails($details);

        // Leyendas
        if (isset($noteData['leyendas']) && !empty($noteData['leyendas'])) {
            $legends = $this->createLegends($noteData['leyendas']);
            $note->setLegends($legends);
        }

        return $note;
    }

    protected function getFormaPago(array $invoiceData)
    {
        $formaPagoTipo = $invoiceData['forma_pago_tipo'] ?? 'Contado';
        
        if ($formaPagoTipo === 'Contado') {
            return new FormaPagoContado();
        }
        
        if ($formaPagoTipo === 'Credito') {
            // El monto total se puede pasar al constructor o dejar null
            $montoTotal = $invoiceData['mto_imp_venta'] ?? null;
            return new FormaPagoCredito($montoTotal);
        }
        
        return new FormaPagoContado();
    }

    protected function createSaleDetails(array $detalles): array
    {
        $details = [];
        
        foreach ($detalles as $detalle) {
            $item = new SaleDetail();
            $item->setCodProducto($detalle['codigo'])
                ->setUnidad($detalle['unidad'])
                ->setDescripcion($detalle['descripcion'])
                ->setCantidad($detalle['cantidad'])
                ->setMtoValorUnitario($detalle['mto_valor_unitario'])
                ->setMtoValorVenta($detalle['mto_valor_venta'])
                ->setMtoBaseIgv($detalle['mto_base_igv'])
                ->setPorcentajeIgv($detalle['porcentaje_igv'])
                ->setIgv($detalle['igv'])
                ->setTipAfeIgv($detalle['tip_afe_igv'])
                ->setTotalImpuestos($detalle['total_impuestos'])
                ->setMtoPrecioUnitario($detalle['mto_precio_unitario']);

            // ISC (opcional)
            if (isset($detalle['isc']) && $detalle['isc'] > 0) {
                $item->setIsc($detalle['isc'])
                     ->setTipSisIsc($detalle['tip_sis_isc'] ?? '01')
                     ->setMtoBaseIsc($detalle['mto_base_isc'] ?? $detalle['mto_valor_venta']);
            }

            // ICBPER (opcional)
            if (isset($detalle['icbper']) && $detalle['icbper'] > 0) {
                $item->setIcbper($detalle['icbper'])
                     ->setFactorIcbper($detalle['factor_icbper'] ?? 1);
            }

            // Valor gratuito (solo para operaciones gratuitas)
            if (isset($detalle['mto_valor_gratuito']) && $detalle['mto_valor_gratuito'] > 0) {
                $item->setMtoValorGratuito($detalle['mto_valor_gratuito']);
            }

            $details[] = $item;
        }
        
        return $details;
    }

    protected function createLegends(array $leyendas): array
    {
        $legends = [];
        
        foreach ($leyendas as $leyenda) {
            $legend = new Legend();
            $legend->setCode($leyenda['code'])
                   ->setValue($leyenda['value']);
            $legends[] = $legend;
        }
        
        return $legends;
    }

    protected function createDocuments(array $documentos): array
    {
        $documents = [];
        
        foreach ($documentos as $doc) {
            $document = new \Greenter\Model\Sale\Document();
            $document->setTipoDoc($doc['tipo_doc'])
                     ->setNroDoc($doc['nro_doc']);
            $documents[] = $document;
        }
        
        return $documents;
    }

    public function sendDocument($document)
    {
        try {
            $result = $this->see->send($document);
            
            return [
                'success' => $result->isSuccess(),
                'xml' => $this->see->getFactory()->getLastXml(),
                'cdr_response' => $result->isSuccess() ? $result->getCdrResponse() : null,
                'cdr_zip' => $result->isSuccess() ? $result->getCdrZip() : null,
                'error' => $result->isSuccess() ? null : $result->getError()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'xml' => null,
                'cdr_response' => null,
                'cdr_zip' => null,
                'error' => (object)[
                    'code' => 'EXCEPTION',
                    'message' => $e->getMessage()
                ]
            ];
        }
    }

    public function getXmlSigned($document): ?string
    {
        try {
            return $this->see->getXmlSigned($document);
        } catch (Exception $e) {
            return null;
        }
    }

    public function createRetention($retentionData)
    {
        $retention = new Retention();
        
        // Configuración básica
        $retention->setSerie($retentionData['serie'])
                  ->setCorrelativo($retentionData['correlativo'])
                  ->setFechaEmision(new \DateTime($retentionData['fecha_emision']))
                  ->setCompany($this->getGreenterCompany())
                  ->setProveedor($this->getGreenterClient($retentionData['proveedor']))
                  ->setRegimen($retentionData['regimen'])
                  ->setTasa($retentionData['tasa'])
                  ->setObservacion($retentionData['observacion'] ?? '')
                  ->setImpRetenido($retentionData['imp_retenido'])
                  ->setImpPagado($retentionData['imp_pagado']);

        // Crear detalles de retención
        $details = [];
        foreach ($retentionData['detalles'] as $detalleData) {
            $detail = new RetentionDetail();
            $detail->setTipoDoc($detalleData['tipo_doc'])
                   ->setNumDoc($detalleData['num_doc'])
                   ->setFechaEmision(new \DateTime($detalleData['fecha_emision']))
                   ->setFechaRetencion(new \DateTime($detalleData['fecha_retencion']))
                   ->setMoneda($detalleData['moneda'])
                   ->setImpTotal($detalleData['imp_total'])
                   ->setImpPagar($detalleData['imp_pagar'])
                   ->setImpRetenido($detalleData['imp_retenido']);

            // Crear pagos
            $pagos = [];
            foreach ($detalleData['pagos'] as $pagoData) {
                $pago = new Payment();
                $pago->setMoneda($pagoData['moneda'])
                     ->setFecha(new \DateTime($pagoData['fecha']))
                     ->setImporte($pagoData['importe']);
                $pagos[] = $pago;
            }
            $detail->setPagos($pagos);

            // Crear tipo de cambio
            $exchange = new Exchange();
            $exchange->setFecha(new \DateTime($detalleData['tipo_cambio']['fecha']))
                     ->setFactor($detalleData['tipo_cambio']['factor'])
                     ->setMonedaObj($detalleData['tipo_cambio']['moneda_obj'])
                     ->setMonedaRef($detalleData['tipo_cambio']['moneda_ref']);
            $detail->setTipoCambio($exchange);

            $details[] = $detail;
        }

        $retention->setDetails($details);
        
        return $retention;
    }

    public function sendRetention($retention)
    {
        try {
            // Configurar endpoint específico para retenciones
            $this->see->setService(SunatEndpoints::RETENCION_BETA);
            
            $result = $this->see->send($retention);
            
            return [
                'success' => $result->isSuccess(),
                'xml' => $this->see->getFactory()->getLastXml(),
                'cdr_response' => $result->isSuccess() ? $result->getCdrResponse() : null,
                'cdr_zip' => $result->isSuccess() ? $result->getCdrZip() : null,
                'error' => $result->isSuccess() ? null : $result->getError()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'xml' => null,
                'cdr_response' => null,
                'cdr_zip' => null,
                'error' => (object)[
                    'code' => 'EXCEPTION',
                    'message' => $e->getMessage()
                ]
            ];
        }
    }

    /**
     * Prepara y valida el certificado para uso con Greenter
     */
    protected function prepareCertificate(string $certificadoPem): string
    {
        // Limpiar el certificado removiendo espacios y caracteres innecesarios
        $certificadoLimpio = trim($certificadoPem);
        
        // Normalizar saltos de línea (muy importante para ASN.1)
        $certificadoLimpio = str_replace(["\r\n", "\r"], "\n", $certificadoLimpio);
        
        // Remover espacios en blanco al inicio y final de cada línea
        $lines = explode("\n", $certificadoLimpio);
        $lines = array_map('trim', $lines);
        $certificadoLimpio = implode("\n", $lines);
        
        // Validar que tenga la estructura correcta
        if (!$this->isValidPemStructure($certificadoLimpio)) {
            throw new Exception('El certificado PEM no tiene una estructura válida');
        }
        
        // Reconstruir completamente el certificado para máxima compatibilidad
        $certificadoLimpio = $this->reconstructPemCertificate($certificadoLimpio);
        
        return $certificadoLimpio;
    }

    /**
     * Valida la estructura básica del certificado PEM
     */
    protected function isValidPemStructure(string $pem): bool
    {
        // Debe tener clave privada y certificado (en cualquier orden)
        $hasPrivateKey = strpos($pem, '-----BEGIN PRIVATE KEY-----') !== false && 
                        strpos($pem, '-----END PRIVATE KEY-----') !== false;
        
        $hasCertificate = strpos($pem, '-----BEGIN CERTIFICATE-----') !== false && 
                         strpos($pem, '-----END CERTIFICATE-----') !== false;
        
        // También puede ser RSA PRIVATE KEY
        $hasRsaPrivateKey = strpos($pem, '-----BEGIN RSA PRIVATE KEY-----') !== false && 
                           strpos($pem, '-----END RSA PRIVATE KEY-----') !== false;
        
        $hasValidPrivateKey = $hasPrivateKey || $hasRsaPrivateKey;
        
        Log::info("Validando estructura PEM: Certificate={$hasCertificate}, PrivateKey={$hasValidPrivateKey}");
        
        return $hasValidPrivateKey && $hasCertificate;
    }

    /**
     * Normaliza los headers del certificado PEM
     */
    protected function normalizePemHeaders(string $pem): string
    {
        // Asegurar que los headers estén en líneas separadas
        $pem = str_replace('-----BEGIN PRIVATE KEY-----', "\n-----BEGIN PRIVATE KEY-----\n", $pem);
        $pem = str_replace('-----END PRIVATE KEY-----', "\n-----END PRIVATE KEY-----\n", $pem);
        $pem = str_replace('-----BEGIN CERTIFICATE-----', "\n-----BEGIN CERTIFICATE-----\n", $pem);
        $pem = str_replace('-----END CERTIFICATE-----', "\n-----END CERTIFICATE-----\n", $pem);
        
        // Limpiar múltiples saltos de línea
        $pem = preg_replace("/\n+/", "\n", $pem);
        
        return trim($pem);
    }

    /**
     * Reconstruye completamente el certificado PEM para máxima compatibilidad
     */
    protected function reconstructPemCertificate(string $pem): string
    {
        $output = [];
        
        // Limpiar completamente el contenido removiendo Bag Attributes y otras líneas no esenciales
        $cleanedPem = $this->removeBagAttributes($pem);
        
        // Extraer clave privada (PRIVATE KEY o RSA PRIVATE KEY)
        $privateKeyExtracted = false;
        if (preg_match('/-----BEGIN PRIVATE KEY-----(.*?)-----END PRIVATE KEY-----/s', $cleanedPem, $matches)) {
            $privateKey = preg_replace('/\s+/', '', $matches[1]);
            $output[] = "-----BEGIN PRIVATE KEY-----";
            $output[] = chunk_split($privateKey, 64, "\n");
            $output[] = "-----END PRIVATE KEY-----";
            $privateKeyExtracted = true;
        } elseif (preg_match('/-----BEGIN RSA PRIVATE KEY-----(.*?)-----END RSA PRIVATE KEY-----/s', $cleanedPem, $matches)) {
            $privateKey = preg_replace('/\s+/', '', $matches[1]);
            $output[] = "-----BEGIN RSA PRIVATE KEY-----";
            $output[] = chunk_split($privateKey, 64, "\n");
            $output[] = "-----END RSA PRIVATE KEY-----";
            $privateKeyExtracted = true;
        }
        
        // Extraer certificado
        if (preg_match('/-----BEGIN CERTIFICATE-----(.*?)-----END CERTIFICATE-----/s', $cleanedPem, $matches)) {
            $certificate = preg_replace('/\s+/', '', $matches[1]);
            $output[] = "-----BEGIN CERTIFICATE-----";
            $output[] = chunk_split($certificate, 64, "\n");
            $output[] = "-----END CERTIFICATE-----";
        }
        
        Log::info("PEM reconstruido: PrivateKey={$privateKeyExtracted}, blocks=" . count($output));
        
        return implode("\n", $output);
    }

    /**
     * Remueve Bag Attributes y otras líneas que interfieren con OpenSSL
     */
    protected function removeBagAttributes(string $pem): string
    {
        $lines = explode("\n", $pem);
        $cleanedLines = [];
        $inPemBlock = false;
        
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            
            // Detectar inicio de bloque PEM
            if (strpos($trimmedLine, '-----BEGIN') === 0) {
                $inPemBlock = true;
            }
            
            // Solo incluir líneas que son parte del bloque PEM
            if ($inPemBlock) {
                $cleanedLines[] = $line;
            }
            
            // Detectar fin de bloque PEM
            if (strpos($trimmedLine, '-----END') === 0) {
                $inPemBlock = false;
            }
        }
        
        return implode("\n", $cleanedLines);
    }

    /**
     * Crea un archivo temporal con el certificado limpio
     */
    protected function createTempCertificate(string $certificadoLimpio): string
    {
        $tempDir = storage_path('app/temp/certificados');
        
        // Crear directorio si no existe
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        // Crear archivo temporal
        $tempPath = $tempDir . '/cert_' . $this->company->ruc . '_' . time() . '.pem';
        file_put_contents($tempPath, $certificadoLimpio);
        
        return $tempPath;
    }

    public function createBoleta(array $boletaData): GreenterInvoice
    {
        // Las boletas usan el mismo modelo Invoice de Greenter
        // pero con tipo de documento '03'
        $boletaData['tipo_documento'] = '03';
        $boletaData['ubl_version'] = $boletaData['ubl_version'] ?? '2.1';
        $boletaData['tipo_operacion'] = $boletaData['tipo_operacion'] ?? '0101';
        
        // Crear la boleta usando el método de factura
        return $this->createInvoice($boletaData);
    }

    public function createSummary(array $summaryData): Summary
    {
        $summary = new Summary();
        
        // Configuración básica
        
        $summary->setFecGeneracion(new \DateTime($summaryData['fecha_resumen']))
                ->setFecResumen(new \DateTime($summaryData['fecha_generacion']))
                ->setCorrelativo($summaryData['correlativo'])
                ->setCompany($this->getGreenterCompany());

        // Crear detalles del resumen
        $details = [];
        foreach ($summaryData['detalles'] as $detalleData) {
            $detail = new SummaryDetail();
            
            $detail->setTipoDoc($detalleData['tipo_documento'])
                   ->setSerieNro($detalleData['serie_numero'])
                   ->setEstado($detalleData['estado'])
                   ->setClienteTipo($detalleData['cliente_tipo'])
                   ->setClienteNro($detalleData['cliente_numero'])
                   ->setTotal($detalleData['total'])
                   ->setMtoOperGravadas($detalleData['mto_oper_gravadas'] ?? 0)
                   ->setMtoOperExoneradas($detalleData['mto_oper_exoneradas'] ?? 0)
                   ->setMtoOperInafectas($detalleData['mto_oper_inafectas'] ?? 0)
                   ->setMtoIGV($detalleData['mto_igv'] ?? 0);

            // Campos opcionales
            if (isset($detalleData['mto_oper_exportacion'])) {
                $detail->setMtoOperExportacion($detalleData['mto_oper_exportacion']);
            }

            if (isset($detalleData['mto_oper_gratuitas'])) {
                $detail->setMtoOperGratuitas($detalleData['mto_oper_gratuitas']);
            }

            if (isset($detalleData['mto_isc'])) {
                $detail->setMtoISC($detalleData['mto_isc']);
            }

            if (isset($detalleData['mto_icbper'])) {
                $detail->setMtoICBPER($detalleData['mto_icbper']);
            }

            if (isset($detalleData['mto_otros_cargos'])) {
                $detail->setMtoOtrosCargos($detalleData['mto_otros_cargos']);
            }

            // Documento de referencia (para notas de crédito/débito)
            if (isset($detalleData['documento_referencia']) && !empty($detalleData['documento_referencia'])) {
                $docRef = new \Greenter\Model\Sale\Document();
                $docRef->setTipoDoc($detalleData['documento_referencia']['tipo_documento'])
                       ->setNroDoc($detalleData['documento_referencia']['numero_documento']);
                $detail->setDocReferencia($docRef);
            }

            // Percepción (opcional)
            if (isset($detalleData['percepcion']) && !empty($detalleData['percepcion'])) {
                $percepcion = new SummaryPerception();
                $percepcion->setCodReg($detalleData['percepcion']['cod_regimen'])
                          ->setTasa($detalleData['percepcion']['tasa'])
                          ->setMtoBase($detalleData['percepcion']['monto_base'])
                          ->setMto($detalleData['percepcion']['monto'])
                          ->setMtoTotal($detalleData['percepcion']['monto_total']);
                $detail->setPercepcion($percepcion);
            }

            $details[] = $detail;
        }

        $summary->setDetails($details);

        return $summary;
    }

    public function sendSummaryDocument($summary)
    {
        try {
            $result = $this->see->send($summary);
            
            return [
                'success' => $result->isSuccess(),
                'xml' => $this->see->getFactory()->getLastXml(),
                'ticket' => $result->isSuccess() ? $result->getTicket() : null,
                'error' => $result->isSuccess() ? null : $result->getError()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'xml' => null,
                'ticket' => null,
                'error' => (object)[
                    'code' => 'EXCEPTION',
                    'message' => $e->getMessage()
                ]
            ];
        }
    }

    public function checkSummaryStatus(string $ticket)
    {
        try {
            $result = $this->see->getStatus($ticket);
            
            return [
                'success' => $result->isSuccess(),
                'cdr_response' => $result->isSuccess() ? $result->getCdrResponse() : null,
                'cdr_zip' => $result->isSuccess() ? $result->getCdrZip() : null,
                'error' => $result->isSuccess() ? null : $result->getError()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'cdr_response' => null,
                'cdr_zip' => null,
                'error' => (object)[
                    'code' => 'EXCEPTION',
                    'message' => $e->getMessage()
                ]
            ];
        }
    }

    public function createDespatch(array $despatchData): Despatch
    {
        Log::info('Creando documento Despatch con datos:', [
            'serie' => $despatchData['serie'] ?? 'N/A',
            'correlativo' => $despatchData['correlativo'] ?? 'N/A',
            'modalidad' => $despatchData['mod_traslado'] ?? 'N/A',
            'has_destinatario' => isset($despatchData['destinatario']),
            'has_conductor' => isset($despatchData['conductor']),
            'has_transportista' => isset($despatchData['transportista'])
        ]);
        
        $despatch = new Despatch();
        
        // Configuración básica
        $despatch->setVersion($despatchData['version'] ?? '2022')
                 ->setTipoDoc($despatchData['tipo_documento'])
                 ->setSerie($despatchData['serie'])
                 ->setCorrelativo($despatchData['correlativo'])
                 ->setFechaEmision(new \DateTime($despatchData['fecha_emision']));

        // Empresa y destinatario
        Log::info("Configurando empresa y destinatario", [
            'has_destinatario_key' => isset($despatchData['destinatario']),
            'destinatario_data' => $despatchData['destinatario'] ?? 'NULL'
        ]);

        $despatch->setCompany($this->getGRECompany())
                 ->setDestinatario($this->getGreenterClient($despatchData['destinatario']));

        // Crear objeto de envío
        $envio = new Shipment();
        $envio->setCodTraslado($despatchData['cod_traslado'])
              ->setModTraslado($despatchData['mod_traslado'])
              ->setFecTraslado(new \DateTime($despatchData['fec_traslado']))
              ->setPesoTotal($despatchData['peso_total'])
              ->setUndPesoTotal($despatchData['und_peso_total']);
              
        // Configurar direcciones según tipo de traslado
        $llegada = new Direction($despatchData['llegada_ubigeo'], $despatchData['llegada_direccion']);
        $partida = new Direction($despatchData['partida_ubigeo'], $despatchData['partida_direccion']);
        
        // Para traslado entre establecimientos (código 04)
        if ($despatchData['cod_traslado'] === '04') {
            $envio->setIndicadores(['SUNAT_Envio_IndicadorTrasladoVehiculoM1L']);
            
            // Agregar RUC y código de local para misma empresa
            $llegada->setRuc('20161515648')->setCodLocal('00002');
            $partida->setRuc('20161515648')->setCodLocal('00001');
        }
        
        $envio->setLlegada($llegada)->setPartida($partida);

        // Agregar número de bultos si está presente
        if (isset($despatchData['num_bultos']) && $despatchData['num_bultos'] > 0) {
            $envio->setNumBultos($despatchData['num_bultos']);
        }

        // Configurar transporte según modalidad
        if ($despatchData['mod_traslado'] === '01') {
            // Transporte público - Transportista
            if (isset($despatchData['transportista'])) {
                $transportista = new Transportist();
                $transportista->setTipoDoc($despatchData['transportista']['tipo_doc'])
                             ->setNumDoc($despatchData['transportista']['num_doc'])
                             ->setRznSocial($despatchData['transportista']['razon_social']);
                
                if (isset($despatchData['transportista']['nro_mtc'])) {
                    $transportista->setNroMtc($despatchData['transportista']['nro_mtc']);
                }
                
                $envio->setTransportista($transportista);
            }
        } else {
            // Transporte privado - Conductor y vehículo
            // Para código 04 (traslado interno) el conductor es opcional
            if (isset($despatchData['conductor']) && $despatchData['cod_traslado'] !== '04') {
                $conductor = new Driver();
                $conductor->setTipo($despatchData['conductor']['tipo'])
                         ->setTipoDoc($despatchData['conductor']['tipo_doc'])
                         ->setNroDoc($despatchData['conductor']['num_doc'])
                         ->setLicencia($despatchData['conductor']['licencia'])
                         ->setNombres($despatchData['conductor']['nombres'])
                         ->setApellidos($despatchData['conductor']['apellidos']);
                
                $envio->setChoferes([$conductor]);
            }

            // Vehículo principal
            if (isset($despatchData['vehiculo_placa'])) {
                $vehiculo = new Vehicle();
                $vehiculo->setPlaca($despatchData['vehiculo_placa']);
                
                // Vehículos secundarios
                if (isset($despatchData['vehiculos_secundarios']) && !empty($despatchData['vehiculos_secundarios'])) {
                    $secundarios = [];
                    foreach ($despatchData['vehiculos_secundarios'] as $secData) {
                        $secundario = new Vehicle();
                        $secundario->setPlaca($secData['placa']);
                        $secundarios[] = $secundario;
                    }
                    $vehiculo->setSecundarios($secundarios);
                }
                
                $envio->setVehiculo($vehiculo);
            }
        }

        $despatch->setEnvio($envio);

        // Crear detalles
        $details = [];
        foreach ($despatchData['detalles'] as $detalleData) {
            $detail = new DespatchDetail();
            $detail->setCantidad($detalleData['cantidad'])
                   ->setUnidad($detalleData['unidad'])
                   ->setDescripcion($detalleData['descripcion'])
                   ->setCodigo($detalleData['codigo']);

            if (isset($detalleData['codigo_sunat'])) {
                $detail->setCodProdSunat($detalleData['codigo_sunat']);
            }

            $details[] = $detail;
        }

        $despatch->setDetails($details);

        // Observaciones
        if (isset($despatchData['observaciones'])) {
            $despatch->setObservacion($despatchData['observaciones']);
        }

        return $despatch;
    }

    protected function getGRECompany()
    {
        // Para GRE usar datos consistentes con las credenciales SOL de test
        $company = new GreenterCompany();
        
        $company->setRuc('20161515648') // Debe coincidir con credenciales SOL
                ->setRazonSocial('EMPRESA DE PRUEBA SUNAT')
                ->setNombreComercial('EMPRESA DE PRUEBA')
                ->setAddress((new Address())
                    ->setUbigueo('150101')
                    ->setDepartamento('LIMA')
                    ->setProvincia('LIMA')
                    ->setDistrito('LIMA')
                    ->setUrbanizacion('-')
                    ->setDireccion('AV. LIMA 123')
                    ->setCodLocal('0000') // Para GRE, código de local
                );

        return $company;
    }

    public function sendDespatchDocument($despatch)
    {
        try {
            Log::info('=== INICIO sendDespatchDocument ===', [
                'serie' => $despatch->getSerie(),
                'correlativo' => $despatch->getCorrelativo()
            ]);
            
            // Para guías de remisión se usa un endpoint específico (getSeeApi)
            Log::info('Obteniendo API...');
            $api = $this->getSeeApi();
            
            Log::info('Enviando a SUNAT...');
            $result = $api->send($despatch);
            
            $error = $result->getError();
            $errorInfo = null;
            
            if ($error) {
                $errorInfo = [
                    'code' => method_exists($error, 'getCode') ? $error->getCode() : 'N/A',
                    'message' => method_exists($error, 'getMessage') ? $error->getMessage() : 'N/A',
                    'class' => get_class($error)
                ];
            }
            
            Log::info('Respuesta de SUNAT para guía:', [
                'success' => $result->isSuccess(),
                'has_error' => $error !== null,
                'error_info' => $errorInfo,
                'xml_generated' => $api->getLastXml() !== null,
                'xml_length' => $api->getLastXml() ? strlen($api->getLastXml()) : 0
            ]);
            
            // Si no es exitoso, revisar el XML para debug
            if (!$result->isSuccess()) {
                $xml = $api->getLastXml();
                if ($xml) {
                    // Guardar XML completo para revisión
                    $xmlPath = storage_path('logs/debug_despatch_' . date('Y-m-d_H-i-s') . '.xml');
                    file_put_contents($xmlPath, $xml);
                    
                    Log::warning('Guía rechazada. XML guardado en:', [
                        'xml_path' => $xmlPath,
                        'error_code' => $errorInfo['code'] ?? 'N/A',
                        'error_message' => $errorInfo['message'] ?? 'N/A',
                        'xml_preview' => substr($xml, 0, 800)
                    ]);
                }
            }
            
            return [
                'success' => $result->isSuccess(),
                'xml' => $api->getLastXml(),
                'ticket' => $result->isSuccess() ? $result->getTicket() : null,
                'error' => $result->isSuccess() ? null : $error
            ];
        } catch (Exception $e) {
            Log::error('Excepción al enviar guía de remisión:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'xml' => null,
                'ticket' => null,
                'error' => (object)[
                    'code' => 'EXCEPTION',
                    'message' => $e->getMessage()
                ]
            ];
        }
    }

    public function checkDespatchStatus(string $ticket)
    {
        try {
            $api = $this->getSeeApi();
            $result = $api->getStatus($ticket);
            
            return [
                'success' => $result->isSuccess(),
                'cdr_response' => $result->isSuccess() ? $result->getCdrResponse() : null,
                'cdr_zip' => $result->isSuccess() ? $result->getCdrZip() : null,
                'error' => $result->isSuccess() ? null : $result->getError()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'cdr_response' => null,
                'cdr_zip' => null,
                'error' => (object)[
                    'code' => 'EXCEPTION',
                    'message' => $e->getMessage()
                ]
            ];
        }
    }

    public function createVoidedDocument(array $voidedData): Voided
    {
        $voided = new Voided();
        
        // Configuración básica
        $voided->setCorrelativo($voidedData['correlativo'])
               ->setFecGeneracion(new \DateTime($voidedData['fecha_referencia'])) // Fecha de documentos a anular
               ->setFecComunicacion(new \DateTime($voidedData['fecha_emision']))  // Fecha de comunicación
               ->setCompany($this->getGreenterCompany());
        
        // Crear detalles de documentos a anular
        $details = [];
        foreach ($voidedData['detalles'] as $detalle) {
            $detail = new VoidedDetail();
            $detail->setTipoDoc($detalle['tipo_documento'])
                   ->setSerie($detalle['serie'])
                   ->setCorrelativo($detalle['correlativo'])
                   ->setDesMotivoBaja($detalle['motivo_especifico']);
            
            $details[] = $detail;
        }
        
        $voided->setDetails($details);
        
        return $voided;
    }

    public function sendVoidedDocument(Voided $voided)
    {
        try {
            $see = $this->initializeSee();
            $result = $see->send($voided);
            
            return [
                'success' => $result->isSuccess(),
                'xml' => $see->getFactory()->getLastXml(),
                'ticket' => $result->isSuccess() ? $result->getTicket() : null,
                'error' => $result->isSuccess() ? null : $result->getError()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'xml' => null,
                'ticket' => null,
                'error' => (object)[
                    'code' => 'EXCEPTION',
                    'message' => $e->getMessage()
                ]
            ];
        }
    }

    public function checkVoidedDocumentStatus(string $ticket)
    {
        try {
            $see = $this->initializeSee();
            $result = $see->getStatus($ticket);
            
            return [
                'success' => $result->isSuccess(),
                'cdr_response' => $result->isSuccess() ? $result->getCdrResponse() : null,
                'cdr_zip' => $result->isSuccess() ? $result->getCdrZip() : null,
                'error' => $result->isSuccess() ? null : $result->getError()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'cdr_response' => null,
                'cdr_zip' => null,
                'error' => (object)[
                    'code' => 'EXCEPTION',
                    'message' => $e->getMessage()
                ]
            ];
        }
    }

    protected function getSeeApi()
    {
        Log::info("Retornando seeApi", [
            'seeApi_exists' => $this->seeApi !== null,
            'seeApi_class' => $this->seeApi ? get_class($this->seeApi) : 'NULL'
        ]);
        return $this->seeApi;
    }

    /**
     * Obtener configuración de servicio específico para logging
     */
    public function getServiceConfiguration(string $serviceType = 'facturacion'): array
    {
        $config = $this->company->getSunatServiceConfig($serviceType);
        
        return [
            'service' => $serviceType,
            'mode' => $this->company->modo_produccion ? 'PRODUCCIÓN' : 'BETA',
            'endpoint' => $config['endpoint'] ?? 'N/A',
            'timeout' => $config['timeout'] ?? 30,
            'company_ruc' => $this->company->ruc,
            'company_name' => $this->company->razon_social,
        ];
    }

    /**
     * Obtener timeout configurado para servicio específico
     */
    public function getServiceTimeout(string $serviceType = 'facturacion'): int
    {
        return $this->company->getServiceTimeout($serviceType);
    }

    /**
     * Verificar si la empresa tiene configuraciones válidas para un servicio
     */
    public function hasValidConfigurationFor(string $serviceType): bool
    {
        try {
            $config = $this->company->getSunatServiceConfig($serviceType);
            
            $requiredFields = ['endpoint'];
            if ($serviceType === 'guias_remision') {
                $requiredFields[] = 'api_endpoint';
            }
            
            foreach ($requiredFields as $field) {
                if (empty($config[$field])) {
                    return false;
                }
            }
            
            // Verificar credenciales básicas
            if (empty($this->company->ruc) || 
                empty($this->company->usuario_sol) || 
                empty($this->company->clave_sol)) {
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            Log::warning("Error al verificar configuración para {$serviceType}: " . $e->getMessage());
            return false;
        }
    }
}