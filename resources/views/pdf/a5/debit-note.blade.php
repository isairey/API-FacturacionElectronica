@extends('pdf.layouts.a5')

@section('content')
    {{-- Header --}}
    @include('pdf.components.header', [
        'company' => $company, 
        'document' => $document, 
        'tipo_documento_nombre' => $tipo_documento_nombre,
        'fecha_emision' => $fecha_emision,
        'format' => 'a5'
    ])

    {{-- Reference Document --}}
    @include('pdf.components.reference-document', [
        'documento_afectado' => $documento_afectado,
        'motivo' => $motivo,
        'format' => 'a5'
    ])

    {{-- Client Info --}}
    @include('pdf.components.client-info', [
        'client' => $client,
        'format' => 'a5',
        'fecha_emision' => $fecha_emision,
        'fecha_vencimiento' => $fecha_vencimiento ?? null,
        'totales' => $totales ?? []
    ])

    {{-- Items Table --}}
    @include('pdf.components.items-table', [
        'detalles' => $detalles,
        'format' => 'a5'
    ])

    {{-- Total En Letras --}}
    @include('pdf.components.total-letras', [
        'total_en_letras' => $total_en_letras ?? '',
        'totales' => $totales ?? [],
        'format' => 'a5'
    ])

    {{-- Totals with QR --}}
    @include('pdf.components.totals-original', [
        'document' => $document,
        'format' => 'a5',
        'qr_code' => $qr_code ?? null,
        'hash' => $hash ?? null,
        'fecha_emision' => $fecha_emision,
        'total_en_letras' => $total_en_letras ?? '',
        'totales' => $totales ?? []
    ])

    {{-- Footer --}}
    @include('pdf.components.qr-footer', [
        'qr_code' => null,
        'hash' => $hash ?? null,
        'format' => 'a5'
    ])
@endsection