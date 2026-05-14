{{-- PDF Client Info Component --}}
{{-- Props: $client, $format, $fecha_emision (optional) --}}

@if(in_array($format, ['a4', 'A4', 'a5', 'A5']))
    {{-- A4/A5 Client Info --}}
    <div class="client-info">
        <div>
            <p>
                <b>{{ $client['tipo_documento'] == '6' ? 'RUC' : 'DNI' }}:</b> {{ $client['numero_documento'] ?? 'N/A' }}<br>
                <b>CLIENTE:</b> {{ $client['razon_social'] ?? 'CLIENTE' }}<br>
                @if(isset($client['direccion']) && $client['direccion'])
                    <b>DIRECCIÓN:</b> {{ $client['direccion'] }}
                @endif
            </p>
        </div>
        <div>
            <p>
                <b>FECHA EMISIÓN:</b> {{ $fecha_emision }}<br>
                <b>FECHA VENCIMIENTO:</b> {{ $fecha_vencimiento ?? '-' }}<br>
                <b>MONEDA:</b> {{ $totales['moneda_nombre'] ?? 'SOLES' }}
            </p>
        </div>
    </div>
@else
    {{-- Ticket Client Info --}}
    <div class="client-section">
        <div class="client-row">
            <span class="client-label">CLIENTE:</span> {{ strtoupper($client['razon_social'] ?? $client['nombre'] ?? 'CLIENTE') }}
        </div>
        
        @if(isset($client['numero_documento']))
            <div class="client-row">
                <span class="client-label">{{ $client['tipo_documento'] == '6' ? 'RUC' : ($client['tipo_documento'] == '1' ? 'DNI' : 'DOC') }}:</span> {{ $client['numero_documento'] }}
            </div>
        @endif
        
        @if(isset($client['direccion']) && $client['direccion'])
            <div class="client-row break-word">
                <span class="client-label">DIR:</span> {{ $client['direccion'] }}
            </div>
        @endif
        
        @if(isset($fecha_emision))
            <div class="client-row">
                <span class="client-label">FECHA:</span> {{ $fecha_emision }}
            </div>
        @endif
    </div>
@endif