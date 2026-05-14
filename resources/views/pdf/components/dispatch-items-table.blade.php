{{-- PDF Dispatch Items Table Component --}}
{{-- Props: $detalles, $format --}}
@php
    $maxFilas = in_array($format, ['a5', 'A5']) ? 8 : 10;
    $contador = count($detalles);
@endphp

@if(in_array($format, ['a4', 'A4', 'a5', 'A5']))
    {{-- A4/A5 Dispatch Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th>Nº</th>
                <th>CÓDIGO</th>
                <th>DESCRIPCIÓN</th>
                <th>UNIDAD</th>
                <th>CANTIDAD</th>
                <th>PESO (KG)</th>
            </tr>
        </thead>
        <tbody>
            {{-- Items reales --}}
            @foreach($detalles as $index => $detalle)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detalle['codigo'] ?? '' }}</td>
                    <td>{{ $detalle['descripcion'] ?? '' }}</td>
                    <td>{{ $detalle['unidad'] ?? 'NIU' }}</td>
                    <td>{{ number_format($detalle['cantidad'] ?? 0, 2) }}</td>
                    <td>{{ number_format($detalle['peso'] ?? 0, 3) }}</td>
                </tr>
            @endforeach

            {{-- Filas vacías --}}
            @for($i = $contador; $i < $maxFilas; $i++)
                <tr>
                    <td>&nbsp;</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endfor
        </tbody>
    </table>
@else
    {{-- Ticket Dispatch Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="col-codigo">Cód.</th>
                <th class="col-descripcion">Descripción</th>
                <th class="col-cantidad">Cant.</th>
                <th class="col-peso">Peso</th>
            </tr>
        </thead>
        <tbody>
            @foreach($detalles as $detalle)
                <tr>
                    <td class="text-center">{{ $detalle['codigo'] ?? '-' }}</td>
                    <td class="text-left">{{ Str::limit($detalle['descripcion'] ?? '', 20) }}</td>
                    <td class="text-center">{{ number_format($detalle['cantidad'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($detalle['peso'] ?? 0, 3) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif