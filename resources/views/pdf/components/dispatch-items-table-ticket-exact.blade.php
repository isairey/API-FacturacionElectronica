{{-- PDF Dispatch Ticket Items Component (Exact Design Match) --}}
{{-- Props: $detalles --}}

{{-- Items Header --}}
<div class="items-header">
    <div class="header-cant">Cant</div>
    <div class="header-um">U.M</div>
    <div class="header-cod">COD</div>
    <div class="header-peso">PESO</div>
</div>
<div class="items-header" style="border-top: none; border-bottom: none; font-weight: bold; padding: 1px 0;">
    <div style="width: 100%; text-align: left; padding-left: 5px;">DESCRIPCION</div>
</div>

{{-- Items List --}}
<div class="items-section">
    @forelse($detalles as $index => $detalle)
        <div class="item">
            <div class="item-cant">{{ number_format($detalle['cantidad'] ?? 1, 0) }}</div>
            <div class="item-um">{{ $detalle['unidad'] ?? 'NIU' }}</div>
            <div class="item-cod">{{ $detalle['codigo'] ?? '' }}</div>
            <div class="item-peso">{{ number_format($detalle['peso'] ?? 0, 3) }}</div>
        </div>
        <div class="item-descripcion">{{ strtoupper($detalle['descripcion'] ?? '') }}</div>
    @empty
        <div class="item">
            <div style="width: 100%; text-align: center;">Sin items</div>
        </div>
    @endforelse
</div>