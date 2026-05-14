<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            
            // Identificación del documento
            $table->string('tipo_documento', 2)->default('01'); // 01: Factura
            $table->string('serie', 4);
            $table->string('correlativo', 8);
            $table->string('numero_completo', 15); // Serie-Correlativo (F001-000001)
            
            // Fechas
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento')->nullable();
            
            // Configuración
            $table->string('ubl_version', 3)->default('2.1');
            $table->string('tipo_operacion', 4)->default('0101');
            $table->string('moneda', 3)->default('PEN');
            
            // Forma de pago
            $table->string('forma_pago_tipo', 20)->default('Contado'); // Contado, Credito
            $table->json('forma_pago_cuotas')->nullable(); // Para crédito
            
            // Montos
            $table->decimal('valor_venta', 12, 2)->default(0);
            $table->decimal('mto_oper_gravadas', 12, 2)->default(0);
            $table->decimal('mto_oper_exoneradas', 12, 2)->default(0);
            $table->decimal('mto_oper_inafectas', 12, 2)->default(0);
            $table->decimal('mto_oper_exportacion', 12, 2)->default(0);
            $table->decimal('mto_oper_gratuitas', 12, 2)->default(0);
            $table->decimal('mto_igv_gratuitas', 10, 2)->default(0);
            $table->decimal('mto_igv', 12, 2)->default(0);
            $table->decimal('mto_isc', 12, 2)->default(0);
            $table->decimal('mto_icbper', 12, 2)->default(0);
            $table->decimal('mto_otros_tributos', 12, 2)->default(0);
            $table->decimal('mto_detraccion', 12, 2)->default(0);
            $table->decimal('mto_percepcion', 12, 2)->default(0);
            $table->decimal('mto_retencion', 12, 2)->default(0);
            $table->decimal('total_impuestos', 12, 2)->default(0);
            $table->decimal('sub_total', 12, 2)->default(0);
            $table->decimal('mto_imp_venta', 12, 2)->default(0);
            $table->decimal('mto_anticipos', 12, 2)->default(0);
            
            // Detalles y configuraciones
            $table->json('detalles'); // Items de la factura
            $table->json('leyendas')->nullable();
            $table->json('guias')->nullable(); // Guías relacionadas
            $table->json('documentos_relacionados')->nullable(); // Facturas relacionadas, anticipos, etc.
            $table->json('detraccion')->nullable(); // Información de detracción
            $table->json('percepcion')->nullable(); // Información de percepción
            $table->json('retencion')->nullable(); // Información de retención
            $table->json('datos_adicionales')->nullable();
            
            // Archivos generados
            $table->string('xml_path')->nullable();
            $table->string('cdr_path')->nullable();
            $table->string('pdf_path')->nullable();
            
            // Estado SUNAT
            $table->string('estado_sunat', 20)->default('PENDIENTE'); // PENDIENTE, ENVIADO, ACEPTADO, RECHAZADO
            $table->text('respuesta_sunat')->nullable();
            $table->string('codigo_hash')->nullable();
            
            // Auditoría
            $table->string('usuario_creacion')->nullable();
            $table->timestamps();
            
            $table->unique(['company_id', 'serie', 'correlativo']);
            $table->index(['company_id', 'branch_id']);
            $table->index(['fecha_emision']);
            $table->index(['estado_sunat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};