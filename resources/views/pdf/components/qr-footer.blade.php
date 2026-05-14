{{-- PDF QR Code and Footer Component --}}
{{-- Props: $qr_code (optional), $hash (optional), $format --}}

@if(isset($qr_code) && $qr_code)
    <div class="qr-section">
        <div class="qr-code">
            <img src="{{ $qr_code }}" 
                 alt="Código QR" 
                 style="width: {{ $format === 'a4' ? '80px' : '60px' }}; height: {{ $format === 'a4' ? '80px' : '60px' }};">
        </div>
        <div class="qr-info">
            Representación impresa del comprobante electrónico
        </div>
    </div>
@endif

@if(isset($hash) || true)
    <div class="footer">
        <div>Autorizado mediante Resolución de Superintendencia Nº 097-2012/SUNAT</div>
        <div>Representación impresa del Comprobante de Pago Electrónico</div>
        
        @if(isset($hash) && $hash)
            <div class="hash-section">
                <strong>HASH CDR:</strong> {{ $hash }}
            </div>
        @endif
        
        <div class="hash-section">
            Consulte su comprobante en: {{ config('app.url', 'https://mi-empresa.com') }}
        </div>
    </div>
@endif