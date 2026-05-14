{{-- PDF Ticket Items Component (Exact Design Match) --}}
{{-- Props: $detalles --}}

{{-- Items Header --}}
<div class="items-header">
    <div class="header-cant">Cant</div>
    <div class="header-um">U.M</div>
    <div class="header-cod">COD</div>
    <div class="header-precio">PRECIO</div>
    <div class="header-total">TOTAL</div>
</div>
<div class="items-header" style="border-top: none; border-bottom: none; font-weight: bold; padding: 1px 0;">
    <div style="width: 100%; text-align: left; padding-left: 5px;">DESCRIPCION</div>
</div>

{{-- Items List --}}
<div class="items-section">
    @forelse($detalles as $index => $detalle)
        <div class="item">
            <div class="item-cant">{{ number_format($detalle['cantidad'] ?? 1, 0) }}</div>
            <div class="item-um">{{ $detalle['unidad'] ?? 'UNIDAD' }}</div>
            <div class="item-cod">{{ $detalle['codigo'] ?? '9810007005004' }}</div>
            <div class="item-precio">{{ number_format($detalle['mto_valor_unitario'] ?? 20.00, 2) }}</div>
            <div class="item-total">{{ number_format($detalle['mto_valor_venta'] ?? 20.00, 2) }}</div>
        </div>
        <div class="item-descripcion">{{ strtoupper($detalle['descripcion'] ?? 'POLO BASICO COLOR SMALL') }}</div>
    @empty
        <div class="item">
            <div style="width: 100%; text-align: center;">Sin items</div>
        </div>
    @endforelse
</div>