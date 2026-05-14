{{-- PDF Ticket Client Info Component (Exact Design Match) --}}
{{-- Props: $client, $fecha_emision --}}

<div class="client-section">
    {{-- Client Name --}}
    <div class="client-name">{{ strtoupper($client['razon_social'] ?? 'CAMILO SANCHEZ') }}</div>
    
    {{-- Separator --}}
    <div class="client-separator">---</div>
    
    {{-- Document Number --}}
    <div class="client-details">DNI {{ $client['numero_documento'] ?? '71262017' }}</div>
    
    {{-- Date and Time --}}
    <div class="client-details">
        FECHA: {{ $fecha_emision ?? '06/03/2024' }} HORA: {{ now()->format('H:i:s A') }}
    </div>
</div>