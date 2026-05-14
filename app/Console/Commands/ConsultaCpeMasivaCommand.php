<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Boleta;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Services\ConsultaCpeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ConsultaCpeMasivaCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'consulta-cpe:masiva
                            {--company=* : ID de la empresa (puede especificarse múltiples)}
                            {--tipo=* : Tipo de documento (01,03,07,08) (puede especificarse múltiples)}
                            {--fecha-desde= : Fecha desde (YYYY-MM-DD)}
                            {--fecha-hasta= : Fecha hasta (YYYY-MM-DD)}
                            {--limite=50 : Límite de documentos por empresa}
                            {--solo-pendientes : Solo consultar documentos que no han sido consultados}
                            {--delay=500 : Delay en milisegundos entre consultas}';

    /**
     * The console command description.
     */
    protected $description = 'Realizar consultas masivas de estado CPE a SUNAT';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando consulta masiva CPE...');

        // Obtener parámetros
        $companyIds = $this->option('company') ?: [];
        $tipos = $this->option('tipo') ?: ['01', '03', '07', '08'];
        $fechaDesde = $this->option('fecha-desde') ?: Carbon::now()->subDays(30)->format('Y-m-d');
        $fechaHasta = $this->option('fecha-hasta') ?: Carbon::now()->format('Y-m-d');
        $limite = (int) $this->option('limite');
        $soloPendientes = $this->option('solo-pendientes');
        $delay = (int) $this->option('delay') * 1000; // Convertir a microsegundos

        $this->info("📅 Período: {$fechaDesde} hasta {$fechaHasta}");
        $this->info("📋 Tipos: " . implode(', ', $tipos));
        $this->info("🏢 Límite por empresa: {$limite}");

        // Obtener empresas
        $companies = $this->obtenerEmpresas($companyIds);
        
        if ($companies->isEmpty()) {
            $this->warn('❌ No se encontraron empresas válidas');
            return Command::FAILURE;
        }

        $this->info("🏢 Empresas a procesar: " . $companies->count());

        $resumenGeneral = [
            'total_empresas' => $companies->count(),
            'total_documentos' => 0,
            'exitosos' => 0,
            'fallidos' => 0,
            'tiempo_inicio' => microtime(true)
        ];

        // Procesar cada empresa
        foreach ($companies as $company) {
            $this->line("");
            $this->info("🏢 Procesando empresa: {$company->razon_social} (RUC: {$company->ruc})");

            $resumenEmpresa = $this->procesarEmpresa(
                $company, 
                $tipos, 
                $fechaDesde, 
                $fechaHasta, 
                $limite, 
                $soloPendientes, 
                $delay
            );

            $resumenGeneral['total_documentos'] += $resumenEmpresa['total_procesados'];
            $resumenGeneral['exitosos'] += $resumenEmpresa['exitosos'];
            $resumenGeneral['fallidos'] += $resumenEmpresa['fallidos'];

            $this->mostrarResumenEmpresa($company, $resumenEmpresa);
        }

        $this->mostrarResumenGeneral($resumenGeneral);

        return Command::SUCCESS;
    }

    /**
     * Obtener empresas según los IDs especificados
     */
    private function obtenerEmpresas(array $companyIds)
    {
        $query = Company::where('activo', true);

        if (!empty($companyIds)) {
            $query->whereIn('id', $companyIds);
        }

        return $query->get();
    }

    /**
     * Procesar documentos de una empresa
     */
    private function procesarEmpresa(Company $company, array $tipos, string $fechaDesde, string $fechaHasta, int $limite, bool $soloPendientes, int $delay): array
    {
        $consultaService = new ConsultaCpeService($company);
        $resumen = [
            'total_procesados' => 0,
            'exitosos' => 0,
            'fallidos' => 0,
            'por_tipo' => []
        ];

        foreach ($tipos as $tipo) {
            $this->info("  📄 Procesando tipo: {$tipo}");

            $documentos = $this->obtenerDocumentos($tipo, $company->id, $fechaDesde, $fechaHasta, $limite, $soloPendientes);

            if ($documentos->isEmpty()) {
                $this->line("    ℹ️  No hay documentos para procesar");
                continue;
            }

            $this->line("    📊 Documentos encontrados: " . $documentos->count());

            $progressBar = $this->output->createProgressBar($documentos->count());
            $progressBar->start();

            $resumenTipo = ['total' => $documentos->count(), 'exitosos' => 0, 'fallidos' => 0];

            foreach ($documentos as $documento) {
                $resultado = $consultaService->consultarComprobante($documento);

                if ($resultado['success']) {
                    $resumenTipo['exitosos']++;
                } else {
                    $resumenTipo['fallidos']++;
                }

                $progressBar->advance();

                // Delay entre consultas
                if ($delay > 0) {
                    usleep($delay);
                }
            }

            $progressBar->finish();
            $this->line("");

            $resumen['por_tipo'][$tipo] = $resumenTipo;
            $resumen['total_procesados'] += $resumenTipo['total'];
            $resumen['exitosos'] += $resumenTipo['exitosos'];
            $resumen['fallidos'] += $resumenTipo['fallidos'];
        }

        return $resumen;
    }

    /**
     * Obtener documentos según el tipo
     */
    private function obtenerDocumentos(string $tipo, int $companyId, string $fechaDesde, string $fechaHasta, int $limite, bool $soloPendientes)
    {
        $query = null;

        switch ($tipo) {
            case '01':
                $query = Invoice::where('company_id', $companyId);
                break;
            case '03':
                $query = Boleta::where('company_id', $companyId);
                break;
            case '07':
                $query = CreditNote::where('company_id', $companyId);
                break;
            case '08':
                $query = DebitNote::where('company_id', $companyId);
                break;
            default:
                return collect([]);
        }

        $query->whereBetween('fecha_emision', [$fechaDesde, $fechaHasta])
              ->whereIn('estado_sunat', ['ACEPTADO', 'RECHAZADO']);

        if ($soloPendientes) {
            $query->whereNull('consulta_cpe_fecha');
        }

        return $query->orderBy('fecha_emision', 'desc')
                    ->limit($limite)
                    ->get();
    }

    /**
     * Mostrar resumen por empresa
     */
    private function mostrarResumenEmpresa(Company $company, array $resumen)
    {
        $this->line("");
        $this->info("  📊 Resumen para {$company->razon_social}:");
        $this->line("    • Total procesados: {$resumen['total_procesados']}");
        $this->line("    • Exitosos: {$resumen['exitosos']}");
        $this->line("    • Fallidos: {$resumen['fallidos']}");

        if (!empty($resumen['por_tipo'])) {
            foreach ($resumen['por_tipo'] as $tipo => $datos) {
                $nombreTipo = $this->getNombreTipo($tipo);
                $this->line("    • {$nombreTipo}: {$datos['exitosos']}/{$datos['total']} exitosos");
            }
        }
    }

    /**
     * Mostrar resumen general
     */
    private function mostrarResumenGeneral(array $resumen)
    {
        $tiempoTotal = microtime(true) - $resumen['tiempo_inicio'];
        $porcentajeExito = $resumen['total_documentos'] > 0 
            ? round(($resumen['exitosos'] / $resumen['total_documentos']) * 100, 2) 
            : 0;

        $this->line("");
        $this->info("🎯 RESUMEN GENERAL:");
        $this->line("==================");
        $this->line("• Empresas procesadas: {$resumen['total_empresas']}");
        $this->line("• Total documentos: {$resumen['total_documentos']}");
        $this->line("• Consultas exitosas: {$resumen['exitosos']}");
        $this->line("• Consultas fallidas: {$resumen['fallidos']}");
        $this->line("• Porcentaje de éxito: {$porcentajeExito}%");
        $this->line("• Tiempo total: " . round($tiempoTotal, 2) . " segundos");

        if ($resumen['exitosos'] > 0) {
            $this->info("✅ Proceso completado exitosamente");
        } else {
            $this->warn("⚠️  No se pudieron procesar documentos correctamente");
        }
    }

    /**
     * Obtener nombre del tipo de documento
     */
    private function getNombreTipo(string $tipo): string
    {
        return match($tipo) {
            '01' => 'Facturas',
            '03' => 'Boletas',
            '07' => 'Notas de Crédito',
            '08' => 'Notas de Débito',
            default => "Tipo {$tipo}"
        };
    }
}