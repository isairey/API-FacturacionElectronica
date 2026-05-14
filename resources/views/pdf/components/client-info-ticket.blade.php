{{-- PDF Ticket Client Info Component (Original Style) --}}
{{-- Props: $client, $fecha_emision, $fecha_vencimiento, $totales --}}

{{-- Client Information --}}
<div class="client-section">
    <div class="client-row">
        <span class="client-label">Cliente:</span> {{ strtoupper($client['razon_social'] ?? 'CLIENTE VARIOS') }}
    </div>
    <div class="client-row">
        <span class="client-label">{{ $client['tipo_documento'] == '6' ? 'RUC' : 'DNI' }}:</span> {{ $client['numero_documento'] ?? 'N/A' }}
    </div>
    @if(!empty($client['direccion']))
        <div class="client-row">
            <span class="client-label">Dir:</span> {{ $client['direccion'] }}
        </div>
    @endif
</div>

{{-- Document Details --}}
<div class="document-details">
    <div class="detail-row">
        <span class="client-label">Fecha:</span> {{ $fecha_emision }}
    </div>
    @if($fecha_vencimiento ?? null)
        <div class="detail-row">
            <span class="client-label">Venc:</span> {{ $fecha_vencimiento }}
        </div>
    @endif
    <div class="detail-row">
        <span class="client-label">Moneda:</span> {{ $totales['moneda_nombre'] ?? 'SOLES' }}
    </div>
</div>