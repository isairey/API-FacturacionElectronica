{{-- PDF Dispatch Transport Info Component --}}
{{-- Props: $document, $motivo_traslado, $modalidad_traslado, $peso_total_formatted, $format --}}

@php
    $indicadores = $document->indicadores ?? [];
    $esM1L = is_array($indicadores) && in_array('SUNAT_Envio_IndicadorTrasladoVehiculoM1L', $indicadores);
@endphp

@if(in_array($format, ['a4', 'A4', 'a5', 'A5']))
    {{-- A4/A5 Transport Info --}}
    <div class="transport-info">
        <div class="transport-header">
            <h3>INFORMACIÓN DEL TRASLADO</h3>
        </div>
        <div class="transport-details">
            <div class="transport-row">
                <span class="transport-label"><b>MOTIVO TRASLADO:</b></span>
                <span>{{ $motivo_traslado }}</span>
            </div>
            <div class="transport-row">
                <span class="transport-label"><b>MODALIDAD:</b></span>
                <span>{{ $modalidad_traslado }}</span>
            </div>
            <div class="transport-row">
                <span class="transport-label"><b>PESO TOTAL:</b></span>
                <span>{{ $peso_total_formatted }}</span>
            </div>
            @if(!empty($document->num_bultos))
            <div class="transport-row">
                <span class="transport-label"><b>N° BULTOS:</b></span>
                <span>{{ $document->num_bultos }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Addresses --}}
    <div class="addresses-info">
        <div class="address-section">
            <h4>PUNTO DE PARTIDA</h4>
            <p>{{ $document->partida['direccion'] ?? $document->partida_direccion ?? 'N/E' }}</p>
        </div>
        <div class="address-section">
            <h4>PUNTO DE LLEGADA</h4>
            <p>{{ $document->llegada['direccion'] ?? $document->llegada_direccion ?? 'N/E' }}</p>
        </div>
    </div>

    {{-- M1L or Transport Details --}}
    @if($esM1L)
    <div class="transport-m1l">
        <div class="m1l-header">
            <h4>TRANSPORTE M1L</h4>
        </div>
        <p><strong>Vehículos Menores - Sin conductor específico</strong></p>
    </div>
    @elseif(($document->mod_traslado ?? '02') == '02')
    {{-- Transporte Privado --}}
    @php 
        $vehiculo = $document->vehiculo ?? [];
        $conductor = $vehiculo['conductor'] ?? [];
    @endphp
    @if(!empty($conductor))
    <div class="transport-details-section">
        <div class="transport-details-header">
            <h4>CONDUCTOR Y VEHÍCULO</h4>
        </div>
        <div class="conductor-info">
            <div><strong>Conductor:</strong> {{ ($conductor['nombres'] ?? $document->conductor_nombres ?? 'NO') }} {{ ($conductor['apellidos'] ?? $document->conductor_apellidos ?? 'ESPECIFICADO') }}</div>
            <div><strong>DNI:</strong> {{ $conductor['num_doc'] ?? $document->conductor_num_doc ?? 'N/A' }} | <strong>Licencia:</strong> {{ $conductor['licencia'] ?? $document->conductor_licencia ?? 'N/A' }}</div>
            @if(!empty($vehiculo['placa_principal'] ?? $document->vehiculo_placa ?? ''))
            <div><strong>Vehículo:</strong> {{ $vehiculo['placa_principal'] ?? $document->vehiculo_placa }}</div>
            @endif
        </div>
    </div>
    @endif
    @elseif(($document->mod_traslado ?? '02') == '01')
    {{-- Transporte Público --}}
    @php $transportista = $document->transportista ?? []; @endphp
    @if(!empty($transportista))
    <div class="transport-details-section">
        <div class="transport-details-header">
            <h4>TRANSPORTISTA</h4>
        </div>
        <div class="transportista-info">
            <div><strong>Empresa:</strong> {{ $transportista['razon_social'] ?? $document->transportista_razon_social ?? 'N/E' }}</div>
            <div><strong>RUC:</strong> {{ $transportista['num_doc'] ?? $document->transportista_num_doc ?? 'N/A' }}</div>
            @if(!empty($transportista['nro_mtc'] ?? $document->transportista_nro_mtc ?? ''))
            <div><strong>MTC:</strong> {{ $transportista['nro_mtc'] ?? $document->transportista_nro_mtc }}</div>
            @endif
        </div>
    </div>
    @endif
    @endif
@else
    {{-- Ticket Transport Info --}}
    <div class="transport-section">
        <div class="transport-row">
            <span class="transport-label">MOTIVO:</span> {{ $motivo_traslado }}
        </div>
        <div class="transport-row">
            <span class="transport-label">MODALIDAD:</span> {{ $modalidad_traslado }}
        </div>
        <div class="transport-row">
            <span class="transport-label">PESO:</span> {{ $peso_total_formatted }}
        </div>
        @if(!empty($document->num_bultos))
        <div class="transport-row">
            <span class="transport-label">BULTOS:</span> {{ $document->num_bultos }}
        </div>
        @endif
    </div>
@endif