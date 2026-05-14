@extends('pdf.layouts.50mm')

@section('content')
    {{-- Header --}}
    @include('pdf.components.header-ticket-exact', [
        'company' => $company, 
        'document' => $document, 
        'tipo_documento_nombre' => 'GUÍA DE REMISIÓN ELECTRÓNICA'
    ])

    {{-- Destinatario Info --}}
    @include('pdf.components.dispatch-client-info-ticket-exact', [
        'destinatario' => $destinatario,
        'fecha_emision' => $fecha_emision,
        'fecha_traslado' => $fecha_traslado
    ])

    {{-- Transport Info --}}
    @include('pdf.components.dispatch-transport-info-ticket-exact', [
        'document' => $document,
        'motivo_traslado' => $motivo_traslado ?? 'VENTA',
        'modalidad_traslado' => $modalidad_traslado ?? 'TRANSPORTE PRIVADO',
        'peso_total_formatted' => $peso_total_formatted ?? '0.000 KGM'
    ])

    {{-- Items Table --}}
    @include('pdf.components.dispatch-items-table-ticket-exact', [
        'detalles' => $detalles
    ])

    {{-- Observations --}}
    @if($document->observaciones ?? null)
    @include('pdf.components.dispatch-observations-ticket-exact', [
        'observaciones' => $document->observaciones
    ])
    @endif

    {{-- Footer --}}
    @include('pdf.components.footer-ticket-exact', [
        'document' => $document,
        'qr_code' => $qr_code ?? null,
        'hash' => $hash ?? null,
        'tipo_documento_nombre' => 'GUÍA DE REMISIÓN ELECTRÓNICA'
    ])
@endsection