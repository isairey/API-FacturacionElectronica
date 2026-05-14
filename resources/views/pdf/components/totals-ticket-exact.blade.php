{{-- PDF Ticket Totals Component (Exact Design Match) --}}
{{-- Props: $document, $totales, $total_en_letras --}}

<div class="totals-section">
    {{-- Subtotal --}}
    <div class="total-line">
        <span class="total-text">TOTAL GRAVADO</span>
        <span class="total-dots">........................</span>
        <span class="total-value">(S/) {{ $totales['subtotal_formatted'] ?? '16.95' }}</span>
    </div>
    
    {{-- IGV --}}
    <div class="total-line">
        <span class="total-text">I.G.V</span>
        <span class="total-dots">..............................</span>
        <span class="total-value">(S/) {{ $totales['igv_formatted'] ?? '3.05' }}</span>
    </div>
    
    {{-- Total Final --}}
    <div class="total-line total-final">
        <span class="total-text">TOTAL</span>
        <span class="total-dots">.................................</span>
        <span class="total-value">(S/) {{ $totales['total_formatted'] ?? '20.00' }}</span>
    </div>
</div>

{{-- Total en Letras --}}
<div class="total-letras">
    SON: {{ strtoupper($total_en_letras ?? 'VEINTE CON 00/100 SOLES') }}
</div>

{{-- Payment Info --}}
<div class="payment-info">
    <div><strong>FORMA DE PAGO:</strong> {{ $document->forma_pago_tipo ?? 'EFECTIVO' }}</div>
    <div><strong>COND.VENTA:</strong> {{ $document->condicion_venta ?? 'CONTADO' }}</div>
</div>

{{-- Observations --}}
@if(!empty($document->observaciones))
    <div class="payment-info">
        <div><strong>Observaciones:</strong></div>
        <div>{{ $document->observaciones }}</div>
    </div>
@endif