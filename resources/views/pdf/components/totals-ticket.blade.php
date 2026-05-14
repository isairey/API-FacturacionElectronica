{{-- PDF Ticket Totals Component (Original Style) --}}
{{-- Props: $document, $totales, $format --}}

@if(in_array($format, ['80mm', '50mm']))
    @if($format === '80mm')
        {{-- 80mm Totals --}}
        <div class="totals">
            <table class="totals-table">
                <tr>
                    <td><strong>SUB TOTAL</strong></td>
                    <td class="text-right">PEN {{ $totales['subtotal_formatted'] ?? '0.00' }}</td>
                </tr>
                @if(($document->mto_oper_exoneradas ?? 0) > 0)
                    <tr>
                        <td><strong>OP. EXONERADAS</strong></td>
                        <td class="text-right">PEN {{ number_format($document->mto_oper_exoneradas, 2) }}</td>
                    </tr>
                @endif
                @if(($document->mto_oper_inafectas ?? 0) > 0)
                    <tr>
                        <td><strong>OP. INAFECTAS</strong></td>
                        <td class="text-right">PEN {{ number_format($document->mto_oper_inafectas, 2) }}</td>
                    </tr>
                @endif
                <tr>
                    <td><strong>I.G.V.</strong></td>
                    <td class="text-right">PEN {{ $totales['igv_formatted'] ?? '0.00' }}</td>
                </tr>
                <tr>
                    <td><strong>TOTAL VENTA</strong></td>
                    <td class="text-right">PEN {{ $totales['total_formatted'] ?? '0.00' }}</td>
                </tr>
            </table>
        </div>
    @else
        {{-- 50mm Totals --}}
        <div class="totals-section">
            <div class="total-row">
                <span class="label">Op. Gravadas:</span>
                <span>{{ $totales['moneda'] ?? 'S/' }} {{ $totales['subtotal_formatted'] ?? '0.00' }}</span>
            </div>
            
            @if(($document->mto_oper_exoneradas ?? 0) > 0)
                <div class="total-row">
                    <span class="label">Op. Exoneradas:</span>
                    <span>{{ $totales['moneda'] ?? 'S/' }} {{ number_format($document->mto_oper_exoneradas, 2) }}</span>
                </div>
            @endif
            
            @if(($document->mto_oper_inafectas ?? 0) > 0)
                <div class="total-row">
                    <span class="label">Op. Inafectas:</span>
                    <span>{{ $totales['moneda'] ?? 'S/' }} {{ number_format($document->mto_oper_inafectas, 2) }}</span>
                </div>
            @endif
            
            <div class="total-row">
                <span class="label">IGV (18%):</span>
                <span>{{ $totales['moneda'] ?? 'S/' }} {{ $totales['igv_formatted'] ?? '0.00' }}</span>
            </div>
            
            <div class="total-row total-final">
                <span class="label">TOTAL:</span>
                <span>{{ $totales['moneda'] ?? 'S/' }} {{ $totales['total_formatted'] ?? '0.00' }}</span>
            </div>
        </div>
    @endif
@endif