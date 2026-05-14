@extends('pdf.layouts.a4')

@section('content')
    {{-- Header --}}
    @include('pdf.components.header', [
        'company' => $company, 
        'document' => $document, 
        'tipo_documento_nombre' => 'GUÍA DE REMISIÓN ELECTRÓNICA',
        'fecha_emision' => $fecha_emision,
        'format' => 'a4'
    ])

    {{-- Destinatario Info --}}
    @include('pdf.components.dispatch-client-info', [
        'destinatario' => $destinatario,
        'format' => 'a4',
        'fecha_emision' => $fecha_emision,
        'fecha_traslado' => $fecha_traslado,
        'peso_total_formatted' => $peso_total_formatted ?? '0.000 KGM'
    ])

    {{-- Transport Info --}}
    @include('pdf.components.dispatch-transport-info', [
        'document' => $document,
        'motivo_traslado' => $motivo_traslado ?? 'VENTA',
        'modalidad_traslado' => $modalidad_traslado ?? 'TRANSPORTE PRIVADO',
        'peso_total_formatted' => $peso_total_formatted ?? '0.000 KGM',
        'format' => 'a4'
    ])

    {{-- Items Table --}}
    @include('pdf.components.dispatch-items-table', [
        'detalles' => $detalles,
        'format' => 'a4'
    ])

    {{-- Observations --}}
    @if($document->observaciones ?? null)
    @include('pdf.components.dispatch-observations', [
        'observaciones' => $document->observaciones,
        'format' => 'a4'
    ])
    @endif

    {{-- Footer --}}
    @include('pdf.components.qr-footer', [
        'qr_code' => null,
        'hash' => $hash ?? null,
        'format' => 'a4'
    ])
@endsection