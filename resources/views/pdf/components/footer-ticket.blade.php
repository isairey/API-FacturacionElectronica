{{-- PDF Ticket Footer Component (Original Style) --}}
{{-- Props: $document, $qr_code, $hash, $tipo_documento_nombre --}}

<div class="footer-section">
    {{-- QR Code --}}
    @if(isset($qr_code) && $qr_code)
        <div class="qr-code">
            <img src="{{ $qr_code }}" alt="Código QR">
        </div>
    @endif
    
    {{-- Footer Text --}}
    <div style="margin-top: 2px;">
        Representación impresa del<br>
        {{ $tipo_documento_nombre ?? 'COMPROBANTE ELECTRÓNICO' }}
    </div>
    
    {{-- Observations --}}
    @if(!empty($document->observaciones))
        <div style="margin-top: 3px; font-size: 5px;">
            <strong>Obs:</strong> {{ $document->observaciones }}
        </div>
    @endif
    
    {{-- Leyendas --}}
    @if(!empty($document->leyendas))
        <div style="margin-top: 2px; font-size: 5px;">
            @php
                $leyendas = is_array($document->leyendas) ? $document->leyendas : json_decode($document->leyendas, true);
                $leyendas = $leyendas ?? [];
            @endphp
            @foreach($leyendas as $leyenda)
                • {{ $leyenda['value'] ?? '' }}<br>
            @endforeach
        </div>
    @endif
    
    {{-- Hash --}}
    @if($hash ?? null)
        <div style="margin-top: 2px; font-size: 4px; word-break: break-all;">
            Hash: {{ substr($hash, 0, 10) }}...
        </div>
    @endif
</div>