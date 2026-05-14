<?php

namespace App\Console\Commands;

use App\Services\PdfTemplateService;
use App\Services\PdfService;
use App\Models\Invoice;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\View;

class TestPdfTemplates extends Command
{
    protected $signature = 'pdf:test-templates 
                           {--format=* : Test specific formats (a4, ticket, A4, 50mm, 80mm)}
                           {--document=* : Test specific document types (invoice, boleta, credit-note)}
                           {--optimized : Only test optimized templates}
                           {--legacy : Only test legacy templates}';

    protected $description = 'Test PDF template generation and validate optimized structure';

    protected PdfTemplateService $templateService;
    protected PdfService $pdfService;

    public function __construct(PdfTemplateService $templateService, PdfService $pdfService)
    {
        parent::__construct();
        $this->templateService = $templateService;
        $this->pdfService = $pdfService;
    }

    public function handle()
    {
        $this->info('🧪 Testing PDF Templates Structure');
        $this->newLine();

        // Test format normalization
        $this->testFormatNormalization();
        
        // Test template existence
        $this->testTemplateExistence();
        
        // Test data validation
        $this->testDataValidation();
        
        // Test PDF generation with sample data
        if ($this->confirm('Generate sample PDFs for testing?', true)) {
            $this->testPdfGeneration();
        }

        $this->newLine();
        $this->info('✅ PDF Template testing completed!');
    }

    protected function testFormatNormalization()
    {
        $this->info('📏 Testing format normalization...');
        
        $testCases = [
            '50mm' => 'ticket',
            '80mm' => 'ticket', 
            'ticket' => 'ticket',
            'A4' => 'a4',
            'a4' => 'a4',
            'a5' => 'a4',
        ];

        $passed = 0;
        foreach ($testCases as $input => $expected) {
            $result = $this->templateService->normalizeFormat($input);
            if ($result === $expected) {
                $this->line("  ✅ {$input} → {$result}");
                $passed++;
            } else {
                $this->error("  ❌ {$input} → {$result} (expected {$expected})");
            }
        }

        $this->info("📊 Format normalization: {$passed}/" . count($testCases) . " passed");
        $this->newLine();
    }

    protected function testTemplateExistence()
    {
        $this->info('📁 Testing template existence...');
        
        $formats = $this->option('format') ?: ['a4', 'ticket'];
        $documents = $this->option('document') ?: ['invoice', 'boleta', 'credit-note', 'debit-note'];
        
        $total = 0;
        $found = 0;

        foreach ($formats as $format) {
            foreach ($documents as $document) {
                $total++;
                
                if ($this->option('optimized') || !$this->option('legacy')) {
                    // Test optimized templates
                    $templatePath = $this->templateService->getTemplatePath($document, $format, true);
                    if (View::exists($templatePath)) {
                        $this->line("  ✅ {$templatePath}");
                        $found++;
                    } else {
                        $this->warn("  ⚠️  {$templatePath} (not found)");
                    }
                }
                
                if ($this->option('legacy') || !$this->option('optimized')) {
                    // Test legacy templates
                    $legacyPath = "pdf.{$format}.{$document}";
                    if (View::exists($legacyPath)) {
                        $this->line("  ✅ {$legacyPath} (legacy)");
                    } else {
                        $this->line("  ⚠️  {$legacyPath} (legacy not found)");
                    }
                }
            }
        }

        $this->info("📊 Template existence: {$found}/{$total} optimized templates found");
        $this->newLine();
    }

    protected function testDataValidation()
    {
        $this->info('🔍 Testing data validation...');
        
        $testData = [
            'complete' => [
                'company' => new Company(),
                'client' => ['razon_social' => 'Test Client'],
                'document' => new Invoice(),
                'detalles' => [['descripcion' => 'Test item']]
            ],
            'incomplete' => [
                'company' => new Company(),
                'client' => null,
            ]
        ];

        foreach ($testData as $scenario => $data) {
            $missing = $this->templateService->validateTemplateData('invoice', $data);
            
            if ($scenario === 'complete' && empty($missing)) {
                $this->line("  ✅ Complete data validation passed");
            } elseif ($scenario === 'incomplete' && !empty($missing)) {
                $this->line("  ✅ Incomplete data validation caught: " . implode(', ', $missing));
            } else {
                $this->error("  ❌ Data validation failed for {$scenario}");
            }
        }

        $this->newLine();
    }

    protected function testPdfGeneration()
    {
        $this->info('📄 Testing PDF generation with sample data...');
        
        try {
            // Create sample data
            $sampleData = $this->createSampleData();
            
            $formats = $this->option('format') ?: ['A4', 'ticket'];
            $documents = $this->option('document') ?: ['invoice'];
            
            foreach ($formats as $format) {
                foreach ($documents as $documentType) {
                    $this->line("  🔄 Generating {$documentType} in {$format}...");
                    
                    try {
                        switch ($documentType) {
                            case 'invoice':
                                $pdf = $this->pdfService->generateInvoicePdf($sampleData['invoice'], $format);
                                break;
                            case 'boleta':
                                $pdf = $this->pdfService->generateBoletaPdf($sampleData['boleta'], $format);
                                break;
                            // Add more document types as needed
                        }
                        
                        if (!empty($pdf)) {
                            $this->line("    ✅ {$documentType} PDF generated successfully ({$format})");
                            
                            // Optionally save to storage for manual inspection
                            $filename = "test_{$documentType}_{$format}_" . date('Y-m-d_H-i-s') . '.pdf';
                            file_put_contents(storage_path("app/pdf_tests/{$filename}"), $pdf);
                            $this->line("    💾 Saved to: storage/app/pdf_tests/{$filename}");
                        }
                        
                    } catch (\Exception $e) {
                        $this->error("    ❌ Failed to generate {$documentType} PDF ({$format}): " . $e->getMessage());
                    }
                }
            }
            
        } catch (\Exception $e) {
            $this->error("PDF generation test failed: " . $e->getMessage());
        }
        
        $this->newLine();
    }

    protected function createSampleData()
    {
        // Create sample company
        $company = new Company([
            'ruc' => '12345678901',
            'razon_social' => 'EMPRESA DE PRUEBA S.A.C.',
            'nombre_comercial' => 'Empresa Test',
            'direccion' => 'Av. Test 123, Lima, Perú',
            'telefono' => '01-234-5678',
            'email' => 'test@empresa.com'
        ]);

        // Create sample invoice
        $invoice = new Invoice([
            'numero_completo' => 'F001-00000001',
            'fecha_emision' => now(),
            'moneda' => 'PEN',
            'mto_oper_gravadas' => 100.00,
            'mto_igv' => 18.00,
            'mto_imp_venta' => 118.00,
            'detalles' => json_encode([
                [
                    'codigo' => 'PROD001',
                    'descripcion' => 'Producto de prueba',
                    'cantidad' => 1,
                    'precio_unitario' => 100.00,
                    'valor_venta' => 100.00,
                    'igv' => 18.00,
                    'importe_total' => 118.00
                ]
            ])
        ]);

        $invoice->setRelation('company', $company);

        return [
            'invoice' => $invoice,
            'boleta' => $invoice, // Same structure for testing
        ];
    }
}