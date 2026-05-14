{{-- PDF Dispatch Client Info Component --}}
{{-- Props: $destinatario, $format, $fecha_emision, $fecha_traslado, $peso_total_formatted --}}

@if(in_array($format, ['a4', 'A4', 'a5', 'A5']))
    {{-- A4/A5 Destinatario Info --}}
    <div class="client-info">
        <div>
            <p>
                <b>{{ ($destinatario->tipo_documento ?? '6') == '6' ? 'RUC' : 'DNI' }}:</b> {{ $destinatario->numero_documento ?? 'N/A' }}<br>
                <b>DESTINATARIO:</b> {{ $destinatario->razon_social ?? 'DESTINATARIO' }}<br>
                @if(isset($destinatario->direccion) && $destinatario->direccion)
                    <b>DIRECCIÓN:</b> {{ $destinatario->direccion }}
                @endif
            </p>
        </div>
        <div>
            <p>
                <b>FECHA EMISIÓN:</b> {{ $fecha_emision }}<br>
                <b>FECHA TRASLADO:</b> {{ $fecha_traslado }}<br>
                <b>PESO TOTAL:</b> {{ $peso_total_formatted }}
            </p>
        </div>
    </div>
@else
    {{-- Ticket Destinatario Info --}}
    <div class="client-section">
        <div class="client-row">
            <span class="client-label">DESTINATARIO:</span> {{ strtoupper($destinatario->razon_social ?? 'DESTINATARIO') }}
        </div>
        
        @if(isset($destinatario->numero_documento))
            <div class="client-row">
                <span class="client-label">{{ ($destinatario->tipo_documento ?? '6') == '6' ? 'RUC' : 'DNI' }}:</span> {{ $destinatario->numero_documento }}
            </div>
        @endif
        
        @if(isset($destinatario->direccion) && $destinatario->direccion)
            <div class="client-row break-word">
                <span class="client-label">DIR:</span> {{ $destinatario->direccion }}
            </div>
        @endif
        
        <div class="client-row">
            <span class="client-label">F.EMISIÓN:</span> {{ $fecha_emision }}
        </div>
        
        <div class="client-row">
            <span class="client-label">F.TRASLADO:</span> {{ $fecha_traslado }}
        </div>
    </div>
@endif