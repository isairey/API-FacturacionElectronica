<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanCertificate extends Command
{
    protected $signature = 'sunat:clean-certificate {input} {output}';
    protected $description = 'Limpia un certificado PEM removiendo Bag Attributes y metadatos';

    public function handle()
    {
        $inputPath = $this->argument('input');
        $outputPath = $this->argument('output');

        if (!file_exists($inputPath)) {
            $this->error("❌ No se encontró el archivo: {$inputPath}");
            return;
        }

        $content = file_get_contents($inputPath);
        $cleanedContent = $this->cleanPemContent($content);

        file_put_contents($outputPath, $cleanedContent);
        
        $this->info("✅ Certificado limpiado y guardado en: {$outputPath}");
        $this->info("📝 Contenido limpiado:");
        $this->info($cleanedContent);
    }

    private function cleanPemContent(string $content): string
    {
        $output = [];
        $lines = explode("\n", $content);
        $inPemBlock = false;
        $currentBlock = [];

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            // Detectar inicio de bloque PEM
            if (strpos($trimmedLine, '-----BEGIN') === 0) {
                $inPemBlock = true;
                $currentBlock = [$trimmedLine];
                continue;
            }

            // Detectar fin de bloque PEM
            if (strpos($trimmedLine, '-----END') === 0) {
                $currentBlock[] = $trimmedLine;
                $output = array_merge($output, $currentBlock);
                $inPemBlock = false;
                $currentBlock = [];
                continue;
            }

            // Solo incluir líneas de datos Base64 (sin metadatos)
            if ($inPemBlock && preg_match('/^[A-Za-z0-9+\/=]+$/', $trimmedLine)) {
                $currentBlock[] = $trimmedLine;
            }
        }

        return implode("\n", $output) . "\n";
    }
}