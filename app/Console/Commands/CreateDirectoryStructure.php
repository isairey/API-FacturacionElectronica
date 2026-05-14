<?php

namespace App\Console\Commands;

use App\Services\FileService;
use Illuminate\Console\Command;

class CreateDirectoryStructure extends Command
{
    protected $signature = 'storage:create-structure';

    protected $description = 'Crear la estructura de directorios para almacenamiento de comprobantes organizados por fecha';

    public function handle()
    {
        $this->info('Creando estructura de directorios para comprobantes...');
        
        $fileService = new FileService();
        $fileService->createDirectoryStructure();
        
        $this->info('✓ Estructura de directorios creada exitosamente:');
        $this->line('  - facturas/xml/');
        $this->line('  - facturas/cdr/');  
        $this->line('  - facturas/pdf/');
        $this->line('  - boletas/xml/');
        $this->line('  - boletas/cdr/');
        $this->line('  - boletas/pdf/');
        $this->line('  - notas-credito/xml/');
        $this->line('  - notas-credito/cdr/');
        $this->line('  - notas-credito/pdf/');
        $this->line('  - notas-debito/xml/');
        $this->line('  - notas-debito/cdr/');
        $this->line('  - notas-debito/pdf/');
        $this->line('  - guias-remision/xml/');
        $this->line('  - guias-remision/cdr/');
        $this->line('  - guias-remision/pdf/');
        $this->line('  - resumenes-diarios/xml/');
        $this->line('  - resumenes-diarios/cdr/');
        $this->line('  - resumenes-diarios/pdf/');
        
        $this->info('Los archivos se organizarán automáticamente en subcarpetas por fecha (formato: ddmmaaaa)');
        $this->info('Ejemplo: facturas/xml/02092025/F001-123.xml');
        
        return Command::SUCCESS;
    }
}