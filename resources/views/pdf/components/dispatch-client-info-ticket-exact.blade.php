{{-- PDF Dispatch Ticket Client Info Component (Exact Design Match) --}}
{{-- Props: $destinatario, $fecha_emision, $fecha_traslado --}}

<div class="client-section">
    {{-- Destinatario Name --}}
    <div class="client-name">{{ strtoupper($destinatario->razon_social ?? 'DESTINATARIO') }}</div>
    
    {{-- Separator --}}
    <div class="client-separator">---</div>
    
    {{-- Document Number --}}
    <div class="client-details">{{ ($destinatario->tipo_documento ?? '6') == '6' ? 'RUC' : 'DNI' }} {{ $destinatario->numero_documento ?? 'N/A' }}</div>
    
    {{-- Dates --}}
    <div class="client-details">
        F.EMISIÓN: {{ $fecha_emision ?? '' }} F.TRASLADO: {{ $fecha_traslado ?? '' }}
    </div>
</div>