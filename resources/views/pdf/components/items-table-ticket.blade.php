{{-- PDF Ticket Items Component (SUNAT Standard Style) --}}
{{-- Props: $detalles, $format --}}

@if(in_array($format, ['80mm', '50mm', 'ticket']))
    <div class="items-section">
        @forelse($detalles as $index => $detalle)
            <div class="item">
                <div class="item-line">
                    {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }} {{ $detalle['codigo'] ?? '' }} {{ strtoupper($detalle['descripcion'] ?? '') }}
                </div>
                <div class="item-details">
                    {{ number_format($detalle['cantidad'] ?? 0, 2) }} {{ $detalle['unidad'] ?? 'NIU' }} x {{ number_format($detalle['mto_valor_unitario'] ?? 0, 2) }} = {{ number_format($detalle['mto_valor_venta'] ?? 0, 2) }}
                </div>
            </div>
        @empty
            <div class="item">
                <div class="item-line">Sin items en este comprobante</div>
            </div>
        @endforelse
    </div>
@endif