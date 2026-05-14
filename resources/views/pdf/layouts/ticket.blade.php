@extends('pdf.layouts.base')

@section('format-styles')
<style>
    /* ================= BASE ================= */
    body {
        font-family: Arial, sans-serif;
        font-size: 9px;
        margin: 0;
        padding: 2px;
        color: #333;
        width: 46mm;
    }

    .container {
        width: 100%;
        padding: 0;
    }

    /* ================= HEADER ================= */
    .header {
        text-align: center;
        margin-bottom: 4px;
        border-bottom: 1px dashed #000;
        padding-bottom: 3px;
    }

    .logo-section-ticket {
        text-align: center;
        margin-bottom: 2px;
    }

    .logo-img-ticket {
        width: 30px;
        height: 30px;
        object-fit: contain;
        display: block;
        margin: 0 auto 2px;
    }

    .company-name {
        font-size: 10px;
        font-weight: bold;
        margin-bottom: 2px;
        text-transform: uppercase;
    }

    .company-details {
        font-size: 8px;
        line-height: 1.1;
        margin-bottom: 2px;
    }

    .document-info {
        font-size: 9px;
        font-weight: bold;
        margin: 2px 0;
        border-top: 1px solid #000;
        border-bottom: 1px solid #000;
        padding: 2px 0;
    }

    /* ================= CLIENT INFO ================= */
    .client-section {
        margin: 3px 0;
        font-size: 8px;
        border-bottom: 1px dashed #000;
        padding-bottom: 3px;
    }

    .client-row {
        margin-bottom: 1px;
        word-wrap: break-word;
    }

    .client-label {
        font-weight: bold;
    }

    /* ================= DOCUMENT DETAILS ================= */
    .document-details {
        margin: 3px 0;
        font-size: 8px;
        border-bottom: 1px dashed #000;
        padding-bottom: 3px;
    }

    .detail-row {
        margin-bottom: 1px;
    }

    /* ================= ITEMS (SUNAT STANDARD) ================= */
    .items-section {
        margin: 3px 0;
        border-top: 1px dashed #000;
        border-bottom: 1px dashed #000;
        padding: 2px 0;
    }

    .item {
        margin-bottom: 2px;
    }

    .item-line {
        font-weight: bold;
        font-size: 9px;
        line-height: 1.2;
    }

    .item-details {
        font-size: 7px;
        color: #666;
        margin-top: 1px;
        text-align: right;
    }

    .text-left { text-align: left; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }

    /* ================= TOTALS ================= */
    .totals-section {
        margin-top: 3px;
        font-size: 7px;
        border-top: 1px solid #000;
        padding-top: 2px;
    }

    .total-line {
        display: block;
        width: 100%;
        margin-bottom: 1px;
        font-weight: bold;
        font-size: 7px;
        line-height: 1.3;
        position: relative;
    }

    .total-text {
        display: inline-block;
        float: left;
        font-weight: bold;
    }

    .total-value {
        display: inline-block;
        float: right;
        font-weight: bold;
    }

    .total-dots {
        display: inline-block;
        float: left;
        font-weight: normal;
        letter-spacing: 0.3px;
        overflow: hidden;
        margin: 0 1px;
    }

    .total-final {
        border-top: 1px solid #000;
        padding-top: 2px;
        margin-top: 1px;
        font-size: 8px;
    }

    .total-final .total-text,
    .total-final .total-value {
        font-size: 8px;
        font-weight: bold;
    }

    /* Clear floats */
    .total-line::after {
        content: "";
        display: table;
        clear: both;
    }

    /* ================= QR CODE ================= */
    .qr-section {
        text-align: center;
        margin: 3px 0;
        border-top: 1px dashed #000;
        padding-top: 3px;
    }

    .qr-code {
        margin: 2px auto;
    }

    .qr-code img {
        width: 35px;
        height: 35px;
    }

    .qr-info {
        font-size: 4px;
        margin-top: 1px;
    }

    /* ================= FOOTER ================= */
    .footer-section {
        margin-top: 4px;
        text-align: center;
        font-size: 6px;
        border-top: 1px dashed #000;
        padding-top: 3px;
    }

    .footer {
        text-align: center;
        margin-top: 4px;
        border-top: 1px solid #000;
        padding-top: 2px;
        font-size: 6px;
    }

    .hash-section {
        word-break: break-all;
        font-size: 4px;
        margin-top: 1px;
    }

    .en-letras {
        font-size: 7px;
        font-weight: bold;
        text-align: center;
        margin: 3px 0;
        word-wrap: break-word;
        border-top: 1px dashed #000;
        border-bottom: 1px dashed #000;
        padding: 2px 0;
    }

    /* ================= ADDITIONAL INFO ================= */
    .additional-info {
        margin: 3px 0;
        font-size: 7px;
        border-top: 1px dashed #000;
        padding-top: 3px;
    }

    .additional-info .section {
        margin-bottom: 2px;
    }

    .additional-info .section-title {
        font-weight: bold;
        margin-bottom: 1px;
    }

    /* ================= REFERENCE DOC ================= */
    .reference-doc {
        margin: 3px 0;
        font-size: 8px;
        border-bottom: 1px dashed #000;
        padding-bottom: 3px;
    }

    .reference-doc .section-title {
        font-weight: bold;
        margin-bottom: 1px;
    }

    /* ================= UTILITIES ================= */
    .text-bold { font-weight: bold; }
    .text-upper { text-transform: uppercase; }
    .dashed-line {
        border-bottom: 1px dashed #000;
        margin: 2px 0;
    }

    @media print {
        body { margin: 0; padding: 1px; }
    }
</style>
@endsection

@section('body-content')
<div class="container">
    @yield('content')
</div>
@endsection