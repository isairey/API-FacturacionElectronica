<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correlatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('tipo_documento', 2); // 01: Factura, 03: Boleta, 07: Nota Crédito, etc.
            $table->string('serie', 4); // F001, B001, etc.
            $table->unsignedInteger('correlativo_actual')->default(0);
            $table->timestamps();
            
            $table->unique(['branch_id', 'tipo_documento', 'serie']);
            $table->index(['branch_id', 'tipo_documento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correlatives');
    }
};
