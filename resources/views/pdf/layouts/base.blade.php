<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $tipo_documento_nombre ?? 'COMPROBANTE ELECTRÓNICO')</title>
    
    {{-- Base Styles --}}
    <style>
        /* ================= RESET & BASE ================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.3;
            color: #000;
            margin: 0;
            padding: 0;
            width: auto;
        }

        /* ================= UTILITIES ================= */
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .text-justify { text-align: justify; }
        
        .font-bold { font-weight: bold; }
        .font-normal { font-weight: normal; }
        
        .uppercase { text-transform: uppercase; }
        .capitalize { text-transform: capitalize; }
        
        .break-word { word-wrap: break-word; word-break: break-word; }
        .no-wrap { white-space: nowrap; }
        
        /* ================= BORDERS ================= */
        .border-top { border-top: 1px solid #000; }
        .border-bottom { border-bottom: 1px solid #000; }
        .border-dashed { border-style: dashed; }
        .border-dotted { border-style: dotted; }
        .border-solid { border-style: solid; }
        
        /* ================= SPACING ================= */
        .mb-0 { margin-bottom: 0; }
        .mb-1 { margin-bottom: 1px; }
        .mb-2 { margin-bottom: 2px; }
        .mb-3 { margin-bottom: 3px; }
        .mb-4 { margin-bottom: 4px; }
        .mb-5 { margin-bottom: 5px; }
        .mb-6 { margin-bottom: 6px; }
        
        .mt-0 { margin-top: 0; }
        .mt-1 { margin-top: 1px; }
        .mt-2 { margin-top: 2px; }
        .mt-3 { margin-top: 3px; }
        .mt-4 { margin-top: 4px; }
        .mt-5 { margin-top: 5px; }
        
        .pb-0 { padding-bottom: 0; }
        .pb-1 { padding-bottom: 1px; }
        .pb-2 { padding-bottom: 2px; }
        .pb-3 { padding-bottom: 3px; }
        .pb-4 { padding-bottom: 4px; }
        .pb-5 { padding-bottom: 5px; }
        
        .pt-0 { padding-top: 0; }
        .pt-1 { padding-top: 1px; }
        .pt-2 { padding-top: 2px; }
        .pt-3 { padding-top: 3px; }
        .pt-4 { padding-top: 4px; }
        .pt-5 { padding-top: 5px; }
        
        /* ================= TABLES ================= */
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 2px;
            vertical-align: top;
        }
        
        .table-bordered th,
        .table-bordered td {
            border: 1px solid #ccc;
        }
        
        .table-bordered-dark th,
        .table-bordered-dark td {
            border: 1px solid #000;
        }
        
        /* ================= SECTIONS ================= */
        .section {
            margin-bottom: 5px;
        }
        
        .section-title {
            font-weight: bold;
            margin-bottom: 2px;
        }
    </style>
    
    {{-- Format-specific styles --}}
    @yield('format-styles')
    
    {{-- Additional custom styles --}}
    @yield('additional-styles')
</head>
<body>
    @yield('body-content')
</body>
</html>