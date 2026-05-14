{{-- PDF Header Component --}}
{{-- Props: $company, $document, $tipo_documento_nombre, $fecha_emision, $format --}}

@php
    // Unificamos la carga de la imagen en Base64 para todos los formatos y evitar problemas de rutas con dompdf.
    $logoPath = public_path('logo_factura.png');

@endphp

@if(in_array($format, ['a4', 'A4', 'a5', 'A5']))
    {{-- A4/A5 Header --}}
    <div class="header">
        <div class="logo-section">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}" alt="Logo Empresa" class="logo-img">
        </div>
        
        <div class="company-section">
            <div class="company-name">{{ strtoupper($company->razon_social ?? 'EMPRESA') }}</div>
            <div class="company-details">
                @if($company->direccion)
                    {{ $company->direccion }}<br>
                @endif
                @if($company->distrito || $company->provincia || $company->departamento)
                    {{ $company->distrito ? $company->distrito . ', ' : '' }}{{ $company->provincia ? $company->provincia . ', ' : '' }}{{ $company->departamento }}<br>
                @endif
                @if($company->telefono)
                    TELÉFONO: {{ $company->telefono }}<br>
                @endif
                @if($company->email)
                    EMAIL: {{ $company->email }}<br>
                @endif
                @if($company->web)
                    WEB: {{ $company->web }}
                @endif
            </div>
        </div>
        
        <div class="document-section">
            <div class="factura-box">
                <p><b>RUC {{ $company->ruc ?? 'N/A' }}</b></p>
                <p><b>{{ strtoupper($tipo_documento_nombre ?? 'FACTURA ELECTRÓNICA') }}</b></p>
                <p><b>{{ $document->serie }}-{{ str_pad($document->correlativo, 6, '0', STR_PAD_LEFT) }}</b></p>
            </div>
        </div>
    </div>
@else
    {{-- Ticket Header (50mm, 80mm, ticket) --}}
    <div class="header">
        <div class="logo-section-ticket">
          
            <img src="data:image/jpg;base64,{{ base64_encode(file_get_contents($logoPath)) }}" alt="Logo Empresa" class="logo-img-ticket">
          
        </div>
        <div class="company-name">{{ strtoupper($company->razon_social ?? 'EMPRESA') }}</div>
        <div class="company-details">
            @if($company->nombre_comercial)
                {{ $company->nombre_comercial }}<br>
            @endif
            RUC: {{ $company->ruc ?? '' }}<br>
            {{ $company->direccion ?? '' }}<br>
            @if($company->telefono)
                Tel: {{ $company->telefono }}<br>
            @endif
            @if($company->email)
                Email: {{ $company->email }}
            @endif
        </div>
        
        <div class="document-info">
            <div>{{ strtoupper($tipo_documento_nombre) }}</div>
            <div>{{ $document->numero_completo }}</div>
            <div>{{ $fecha_emision }}</div>
        </div>
    </div>
@endif