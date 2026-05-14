{{-- PDF Totals Component (Original Style) --}}
{{-- Props: $document, $format, $qr_code, $hash, $fecha_emision, $total_en_letras, $totales --}}

@if(in_array($format, ['a4', 'A4', 'a5', 'A5']))
    <table class="totals-table">
        <tr>
            <td rowspan="7" style="width: 60%;">
                <div class="qr-info-container">
                    <div class="qr-section">
                        @if(isset($qr_code) && $qr_code)
                            <img src="{{ $qr_code }}" alt="Código QR">
                        @endif
                    </div>
                    <div class="info-footer">
                        <b>FECHA EMISIÓN:</b> {{ $fecha_emision }}<br>
                        <b>CONDICIÓN DE PAGO:</b> {{ $document->forma_pago_tipo ?? 'CONTADO' }}<br>
                        @if(!empty($document->observaciones))
                            <b>OBSERVACIONES:</b> {{ $document->observaciones }}<br>
                        @endif
                        @if(!empty($document->leyendas))
                            <b>LEYENDAS:</b><br>
                            @php
                                $leyendas = is_array($document->leyendas) ? $document->leyendas : json_decode($document->leyendas, true);
                                $leyendas = $leyendas ?? [];
                            @endphp
                            @foreach($leyendas as $leyenda)
                                • {{ $leyenda['value'] ?? '' }}<br>
                            @endforeach
                        @endif
                        @if($hash)
                            <b>HASH:</b> {{ substr($hash, 0, 20) }}...<br>
                        @endif
                    </div>
                </div>
            </td>
            <td class="label">Total Ope. Gravadas</td>
            <td>{{ $totales['moneda'] }} {{ $totales['subtotal_formatted'] }}</td>
        </tr>
        <tr>
            <td class="label">Total Ope. Inafectadas</td>
            <td>{{ $totales['moneda'] }} {{ number_format($document->mto_oper_inafectas ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Total Ope. Exoneradas</td>
            <td>{{ $totales['moneda'] }} {{ number_format($document->mto_oper_exoneradas ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Total Descuentos</td>
            <td>{{ $totales['moneda'] }} 0.00</td>
        </tr>
        <tr>
            <td class="label">Total IGV</td>
            <td>{{ $totales['moneda'] }} {{ $totales['igv_formatted'] }}</td>
        </tr>
        <tr>
            <td class="label">Total ISC</td>
            <td>{{ $totales['moneda'] }} {{ number_format($document->mto_isc ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td class="label resaltado">TOTAL A PAGAR</td>
            <td class="resaltado">{{ $totales['moneda'] }} {{ $totales['total_formatted'] }}</td>
        </tr>
    </table>
@endif