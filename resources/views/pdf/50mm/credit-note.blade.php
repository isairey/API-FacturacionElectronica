@extends('pdf.layouts.50mm')

@section('content')
    {{-- Header --}}
    @include('pdf.components.header-ticket-exact', [
        'company' => $company, 
        'document' => $document, 
        'tipo_documento_nombre' => $tipo_documento_nombre
    ])

    {{-- Reference Document --}}
    @if(isset($documento_afectado) || isset($motivo))
        <div class="client-section">
            @if(isset($documento_afectado))
                <div class="client-details">
                    DOC. AFECTADO: {{ $documento_afectado }}
                </div>
            @endif
            @if(isset($motivo))
                <div class="client-details">
                    MOTIVO: {{ $motivo }}
                </div>
            @endif
        </div>
    @endif

    {{-- Client Info --}}
    @include('pdf.components.client-info-ticket-exact', [
        'client' => $client,
        'fecha_emision' => $fecha_emision
    ])

    {{-- Items Table --}}
    @include('pdf.components.items-table-ticket-exact', [
        'detalles' => $detalles
    ])

    {{-- Totals --}}
    @include('pdf.components.totals-ticket-exact', [
        'document' => $document,
        'totales' => $totales ?? [],
        'total_en_letras' => $total_en_letras ?? ''
    ])

    {{-- Footer --}}
    @include('pdf.components.footer-ticket-exact', [
        'document' => $document,
        'qr_code' => $qr_code ?? null,
        'hash' => $hash ?? null,
        'tipo_documento_nombre' => $tipo_documento_nombre ?? 'NOTA DE CRÉDITO ELECTRÓNICA'
    ])
@endsection