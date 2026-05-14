{{-- PDF Dispatch Ticket Transport Info Component --}}
{{-- Props: $document, $motivo_traslado, $modalidad_traslado, $peso_total_formatted --}}

@php
    $indicadores = $document->indicadores ?? [];
    $esM1L = is_array($indicadores) && in_array('SUNAT_Envio_IndicadorTrasladoVehiculoM1L', $indicadores);
@endphp

<div class="transport-section">
    <div class="transport-details">MOTIVO: {{ $motivo_traslado }}</div>
    <div class="transport-details">MODALIDAD: {{ $modalidad_traslado }}</div>
    <div class="transport-details">PESO: {{ $peso_total_formatted }}</div>
    @if(!empty($document->num_bultos))
    <div class="transport-details">BULTOS: {{ $document->num_bultos }}</div>
    @endif
</div>

{{-- Addresses --}}
<div class="addresses-section">
    <div class="address-details"><strong>PARTIDA:</strong></div>
    <div class="address-text">{{ $document->partida['direccion'] ?? $document->partida_direccion ?? 'N/E' }}</div>
    <div class="address-details"><strong>LLEGADA:</strong></div>
    <div class="address-text">{{ $document->llegada['direccion'] ?? $document->llegada_direccion ?? 'N/E' }}</div>
</div>

{{-- M1L or Transport Details --}}
@if($esM1L)
<div class="m1l-section">
    <div class="m1l-header"><strong>TRANSPORTE M1L:</strong></div>
    <div class="m1l-details">Vehículos Menores</div>
</div>
@elseif(($document->mod_traslado ?? '02') == '02')
{{-- Transporte Privado --}}
@php 
    $vehiculo = $document->vehiculo ?? [];
    $conductor = $vehiculo['conductor'] ?? [];
@endphp
@if(!empty($conductor))
<div class="conductor-section">
    <div class="conductor-header"><strong>CONDUCTOR:</strong></div>
    <div class="conductor-details">{{ ($conductor['nombres'] ?? $document->conductor_nombres ?? 'NO') }} {{ ($conductor['apellidos'] ?? $document->conductor_apellidos ?? 'ESPECIFICADO') }}</div>
    <div class="conductor-details">DNI: {{ $conductor['num_doc'] ?? $document->conductor_num_doc ?? 'N/A' }}</div>
    <div class="conductor-details">LIC: {{ $conductor['licencia'] ?? $document->conductor_licencia ?? 'N/A' }}</div>
    @if(!empty($vehiculo['placa_principal'] ?? $document->vehiculo_placa ?? ''))
    <div class="conductor-details"><strong>VEHÍCULO:</strong> {{ $vehiculo['placa_principal'] ?? $document->vehiculo_placa }}</div>
    @endif
</div>
@endif
@elseif(($document->mod_traslado ?? '02') == '01')
{{-- Transporte Público --}}
@php $transportista = $document->transportista ?? []; @endphp
@if(!empty($transportista))
<div class="transportista-section">
    <div class="transportista-header"><strong>TRANSPORTISTA:</strong></div>
    <div class="transportista-details">{{ $transportista['razon_social'] ?? $document->transportista_razon_social ?? 'N/E' }}</div>
    <div class="transportista-details">RUC: {{ $transportista['num_doc'] ?? $document->transportista_num_doc ?? 'N/A' }}</div>
    @if(!empty($transportista['nro_mtc'] ?? $document->transportista_nro_mtc ?? ''))
    <div class="transportista-details">MTC: {{ $transportista['nro_mtc'] ?? $document->transportista_nro_mtc }}</div>
    @endif
</div>
@endif
@endif