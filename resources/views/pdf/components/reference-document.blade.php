{{-- PDF Reference Document Component (for Credit/Debit Notes) --}}
{{-- Props: $documento_afectado, $motivo, $format --}}

@if(in_array($format, ['a4', 'A4', 'a5', 'A5']))
    {{-- A4 Reference Document --}}
    <div class="client-info">
        <div class="client-info-title">DOCUMENTO DE REFERENCIA</div>
        <div class="client-details">
            <div class="row">
                <div class="label">Tipo de Documento:</div>
                <div class="value">{{ $documento_afectado['tipo'] }}</div>
            </div>
            
            <div class="row">
                <div class="label">Número:</div>
                <div class="value">{{ $documento_afectado['numero'] }}</div>
            </div>
            
            @if(isset($documento_afectado['fecha']))
                <div class="row">
                    <div class="label">Fecha:</div>
                    <div class="value">{{ $documento_afectado['fecha'] }}</div>
                </div>
            @endif
            
            <div class="row">
                <div class="label">Motivo:</div>
                <div class="value">{{ $motivo['codigo'] }} - {{ $motivo['descripcion'] }}</div>
            </div>
        </div>
    </div>
@else
    {{-- Ticket Reference Document --}}
    <div class="reference-doc">
        <div class="section-title">DOCUMENTO MODIFICADO:</div>
        <div>{{ $documento_afectado['tipo'] }}: {{ $documento_afectado['numero'] }}</div>
        
        @if(isset($documento_afectado['fecha']))
            <div>Fecha: {{ $documento_afectado['fecha'] }}</div>
        @endif
        
        <div class="section-title">MOTIVO:</div>
        <div>{{ $motivo['codigo'] }} - {{ $motivo['descripcion'] }}</div>
    </div>
@endif