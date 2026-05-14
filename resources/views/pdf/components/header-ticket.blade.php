{{-- PDF Ticket Header Component (Original Style) --}}
{{-- Props: $company, $document, $tipo_documento_nombre, $format --}}

@php
    $logoPath = public_path('logo_factura.png');
@endphp

<div class="header">
    {{-- Logo --}}
    @if(file_exists($logoPath))
        <div class="logo-section-ticket">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}" alt="Logo Empresa" class="logo-img-ticket">
        </div>
    @endif

    {{-- Company Info --}}
    <div class="company-name">{{ strtoupper($company->razon_social ?? 'NOMBRE DE LA EMPRESA') }}</div>
    
    <div class="company-details">
        @if($company->nombre_comercial && $company->nombre_comercial != $company->razon_social)
            {{ $company->nombre_comercial }}<br>
        @endif
        
        {{ $company->direccion ?? 'DIRECCIÓN DE LA EMPRESA' }}<br>
        
        @if($company->distrito || $company->provincia)
            {{ $company->distrito }}{{ $company->provincia ? ', ' . $company->provincia : '' }}<br>
        @endif
        
        @if($company->telefono)
            Tel: {{ $company->telefono }}<br>
        @endif
        
        @if($company->email)
            {{ strtoupper($company->email) }}
        @endif
    </div>

    {{-- Document Info --}}
    <div class="document-info">
        <div>{{ strtoupper($tipo_documento_nombre) }}</div>
        <div>{{ $document->serie }}-{{ str_pad($document->correlativo, 6, '0', STR_PAD_LEFT) }}</div>
        <div>RUC: {{ $company->ruc }}</div>
    </div>
</div>