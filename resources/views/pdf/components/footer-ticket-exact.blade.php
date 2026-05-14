{{-- PDF Ticket Footer Component (Exact Design Match) --}}
{{-- Props: $qr_code, $hash, $document, $tipo_documento_nombre --}}

{{-- QR Code Section --}}
@if(isset($qr_code) && !empty($qr_code))
    <div class="qr-section">
        <div class="qr-code">
            <img src="{{ $qr_code }}" alt="QR Code">
        </div>
    </div>
@endif

{{-- Footer Text --}}
<div class="footer-text">
    Representación impresa de la {{ strtoupper($tipo_documento_nombre ?? 'BOLETA DE VENTA ELECTRONICA') }}.<br>
    Puede verificarla en www.sunat.gob.pe
</div>

{{-- Hash Code --}}
@if(isset($hash) && !empty($hash))
    <div class="footer-auth">
        {{ $hash }}
    </div>
@endif

{{-- Footer URL --}}
<div class="footer-url">
    www.nubefact.com
</div>

{{-- Powered By --}}
<div class="powered-by">
    Powered by NUBEFACT
</div>