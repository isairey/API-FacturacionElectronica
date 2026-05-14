{{-- PDF Ticket Total En Letras Component (Original Style) --}}
{{-- Props: $total_en_letras, $totales --}}

<div class="en-letras">
    SON: {{ strtoupper($total_en_letras ?? '') }} {{ strtoupper($totales['moneda_nombre'] ?? 'SOLES') }}
</div>