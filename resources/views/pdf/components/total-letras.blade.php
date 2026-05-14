{{-- PDF Total En Letras Component --}}
{{-- Props: $total_en_letras, $totales, $format --}}

@if(in_array($format, ['a4', 'A4', 'a5', 'A5']))
    <table class="en-letras">
        <tr>
            <td>SON: {{ strtoupper($total_en_letras) }} {{ strtoupper($totales['moneda_nombre'] ?? 'SOLES') }}</td>
        </tr>
    </table>
@endif