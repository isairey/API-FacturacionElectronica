@extends('pdf.layouts.base')

@section('format-styles')
    <style>
        /* ================= BASE ================= */
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 18cm;
            margin: 20 auto;
            padding: 15px;
            box-sizing: border-box;
            border: 1px solid #000;
            border-radius: 10px;
        }

        /* ================= HEADER ================= */
        .header {
            display: table;
            width: 97%;
            border-bottom: 1px solid #000;
            padding-bottom: 15px;
            table-layout: fixed;
        }

        .header>div {
            display: table-cell;
            vertical-align: top;
            padding: 5px;
        }

        .logo-section {
            width: 25%;
            text-align: left;
            /* border: 1px solid #000; */
        }

        .logo-img {
            width: 145px;
            height: 78;
            object-fit: contain;
            vertical-align: top;
            margin-right: 5px;
           /*  border: 1px solid #000; */
        }

        .company-section {
            width: 50%;
            text-align: left;
            padding: 0 15px;
        }

        .company-name {
            margin: 0 0 5px 0;
            font-size: 16px;
            font-weight: bold;
            color: #000;
        }

        .company-details {
            line-height: 1.4;
            margin: 0;
            font-size: 11px;
            color: #333;
        }

        .document-section {
            width: 25%;
            text-align: center;
            vertical-align: top;
        }

        .factura-box {
            border: 1px solid #000;
            border-radius: 8px;
            padding: 12;
            font-size: 12px;
            background-color: #fff;
            display: inline-block;
            min-width: 180px;
        }

        .factura-box p {
            margin: 2px 0;
            font-weight: bold;
        }

        /* ================= CLIENT INFO ================= */
        .client-info {
            margin-top: 15px;
            margin-bottom: 15px;
            display: table;
            width: 100%;
            font-size: 12px;
            table-layout: fixed;
        }

        .client-info>div {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 5px;
        }

        .client-info p {
            line-height: 1.6;
            margin: 0;
            padding: 5px 0;
        }

        /* ================= TABLA PRINCIPAL ================= */
        .items-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            font-size: 11px;
            border: 1px solid #000;
            /* marco exterior */
            border-radius: 8px;
            margin-bottom: 5px;
        }

        .items-table thead {
            background-color: #f0f0f0;
        }

        .items-table th,
        .items-table td {
            border-right: 1px solid #000;
            padding: 5px;
            text-align: left;
        }

        .items-table thead th {
            border-bottom: 1px solid #000;
        }

     /* Última fila sin borde inferior */
        .items-table tbody tr:first-child th {
            border-right: none
        }

        /* Última columna sin borde derecho */
        .items-table th:last-child,
        .items-table td:last-child {
            border-right: none;
        }

        /* Última fila sin borde inferior */
        .items-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Header sin borde superior */
        .items-table thead th {
            border-top: none;
        }

        /* Esquinas redondeadas para el header */
        .items-table thead th:first-child {
            border-top-left-radius: 6px;
        }

        .items-table thead th:last-child {
            border-top-right-radius: 6px;
        }

        /* Esquinas redondeadas para la última fila */
        .items-table tbody tr:last-child td:first-child {
            border-bottom-left-radius: 6px;
        }

        .items-table tbody tr:last-child td:last-child {
            border-bottom-right-radius: 6px;
        }

        /* Columnas numéricas alineadas a la derecha */
        .items-table th:nth-child(5),
        .items-table th:nth-child(6),
        .items-table td:nth-child(5),
        .items-table td:nth-child(6), 
        .items-table td:nth-child(7), {
            text-align: right;
        }

        /* ================= SON EN LETRAS ================= */
        .en-letras {
            margin-top: 5px;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #000;
            border-radius: 8px;
        }

        .en-letras td {
            text-align: center;
            font-weight: bold;
            padding: 6px;
            font-size: 11px;
            border: none;
        }

        /* ================= TOTALES ================= */
        .totals-table {
            margin-top: 10px;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #000;
            border-radius: 8px;
        }

        .totals-table td {
            padding: 2px 10px;
            font-size: 11px;
            vertical-align: top;
            line-height: 1.2;
            border-right: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .totals-table td:last-child {
            border-right: none;
        }

        .totals-table tr:last-child td {
            border-bottom: none;
        }

        .totals-table .label {
            text-align: right;
            font-weight: bold;
            width: 150px;
        }

        .totals-table .resaltado {
            background: #f0f0f0;
            font-weight: bold;
        }

        /* Info + QR en misma celda */
        .qr-info-container {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

     /*    .qr-section {
            display: table-cell;
            width: 80;
            vertical-align: top;
            text-align: center;
            padding-right: 5px;
        } */

        .qr-section img {
            width: 100px;
            height: 100px;
            display: block;
            margin: 0 auto;
        }

        .info-footer {
            display: table-cell;
            font-size: 10px;
            text-align: left;
            vertical-align: top;
            padding-left: 10px;
            line-height: 1.4;
          /*   border: 1px solid #000; */
        }

        /* ================= FOOTER EXTRA ================= */
        .footer {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #000;
            border-radius: 8px;
            background-color: #f9f9f9;
            font-size: 10px;
            line-height: 1.4;
        }

        /* ================= PRINT ================= */
        @media print {
            body {
                margin: 0;
            }

            .container {
                border: none;
                padding: 0;
            }
        }
    </style>
@endsection

@section('body-content')
    <div class="container">
        @yield('content')
    </div>
@endsection
