{{-- PDF Ticket Header Component (Exact Design Match) --}}
{{-- Props: $company, $document, $tipo_documento_nombre --}}

@php
    $logoPath = public_path('logo_factura.png');
@endphp

<div class="header">
    {{-- Logo --}}
    @if(file_exists($logoPath))
        <div class="logo-section-ticket">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}" alt="Logo" class="logo-img-ticket">
        </div>
    @endif

    {{-- Company Name --}}
    <div class="company-name">{{ strtoupper($company->razon_social ?? 'EMPRESA DEMO SAC') }}</div>
    
    {{-- RUC --}}
    <div class="company-ruc">RUC: {{ $company->ruc ?? '20100100100' }}</div>
    
    {{-- Company Details --}}
    <div class="company-details">
        {{ $company->direccion ?? 'CALLE LAS NORMAS 123' }}<br>
        {{ $company->distrito ?? 'CALLAO' }} {{ $company->codigo_postal ?? '654 321' }}<br>
        Correo: {{ $company->email ?? 'Administrador@facturas.net' }}<br>
        Web: {{ $company->website ?? 'www.facturas.net' }}
    </div>

    {{-- Document Title --}}
    <div class="document-title">{{ strtoupper($tipo_documento_nombre ?? 'BOLETA DE VENTA ELECTRONICA') }}</div>
    
    {{-- Document Number --}}
    <div class="document-number">{{ $document->serie ?? 'B002' }} - {{ str_pad($document->correlativo ?? '10300686', 8, '0', STR_PAD_LEFT) }}</div>
</div>