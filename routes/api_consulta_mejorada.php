<?php

/**
 * Rutas adicionales para consulta CPE mejorada
 * Incluir en routes/api.php después de las rutas existentes
 */

use App\Http\Controllers\Api\ConsultaCpeControllerMejorado;

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    
    // ========================
    // CONSULTA CPE MEJORADA (CON FALLBACK SOAP)
    // ========================
    Route::prefix('consulta-cpe-v2')->group(function () {
        // Consultas individuales mejoradas
        Route::post('/factura/{id}', [ConsultaCpeControllerMejorado::class, 'consultarFacturaMejorada'])
            ->name('cpe-v2.factura');
        Route::post('/factura/{id}/con-cdr', [ConsultaCpeControllerMejorado::class, 'consultarFacturaConCdr'])
            ->name('cpe-v2.factura-con-cdr');
        Route::post('/boleta/{id}', [ConsultaCpeControllerMejorado::class, 'consultarBoletaMejorada'])
            ->name('cpe-v2.boleta');
        
        // Consulta por datos directos (sin documento en BD)
        Route::post('/por-datos', [ConsultaCpeControllerMejorado::class, 'consultarPorDatos'])
            ->name('cpe-v2.por-datos');
        
        // Consulta masiva mejorada
        Route::post('/masivo', [ConsultaCpeControllerMejorado::class, 'consultarMasiva'])
            ->name('cpe-v2.masivo');
        
        // Validación de documentos
        Route::get('/validar/{tipo}/{id}', [ConsultaCpeControllerMejorado::class, 'validarDocumento'])
            ->name('cpe-v2.validar');
        
        // Gestión de CDRs
        Route::get('/cdr/{companyId}', [ConsultaCpeControllerMejorado::class, 'listarCdrs'])
            ->name('cpe-v2.listar-cdrs');
        Route::get('/cdr/{companyId}/{filename}', [ConsultaCpeControllerMejorado::class, 'descargarCdr'])
            ->name('cpe-v2.descargar-cdr');
    });
});