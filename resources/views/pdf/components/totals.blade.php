{{-- PDF Totals Component --}}
{{-- Props: $document, $format, $leyendas (optional) --}}

@if(in_array($format, ['a4', 'A4', 'a5', 'A5']))
    {{-- A4 Totals --}}
    <div class="totals-section">
        <div class="totals-left">
            @if(isset($leyendas) && !empty($leyendas))
                <div class="additional-info">
                    <div class="section-title">INFORMACIÓN ADICIONAL</div>
                    <div class="content">
                        @foreach($leyendas as $leyenda)
                            <p><strong>{{ $leyenda['codigo'] ?? '' }}:</strong> {{ $leyenda['descripcion'] ?? '' }}</p>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        
        <div class="totals-right">
            <table class="totals-table">
                @if($document->mto_oper_gravadas > 0)
                    <tr>
                        <td class="label">Operaciones Gravadas:</td>
                        <td class="value">{{ $document->moneda }} {{ number_format($document->mto_oper_gravadas, 2) }}</td>
                    </tr>
                @endif
                
                @if($document->mto_oper_exoneradas > 0)
                    <tr>
                        <td class="label">Operaciones Exoneradas:</td>
                        <td class="value">{{ $document->moneda }} {{ number_format($document->mto_oper_exoneradas, 2) }}</td>
                    </tr>
                @endif
                
                @if($document->mto_oper_inafectas > 0)
                    <tr>
                        <td class="label">Operaciones Inafectas:</td>
                        <td class="value">{{ $document->moneda }} {{ number_format($document->mto_oper_inafectas, 2) }}</td>
                    </tr>
                @endif
                
                @if($document->mto_oper_exportacion > 0)
                    <tr>
                        <td class="label">Operaciones de Exportación:</td>
                        <td class="value">{{ $document->moneda }} {{ number_format($document->mto_oper_exportacion, 2) }}</td>
                    </tr>
                @endif
                
                @if($document->mto_oper_gratuitas > 0)
                    <tr>
                        <td class="label">Operaciones Gratuitas:</td>
                        <td class="value">{{ $document->moneda }} {{ number_format($document->mto_oper_gratuitas, 2) }}</td>
                    </tr>
                @endif
                
                @if($document->mto_igv > 0)
                    <tr>
                        <td class="label">IGV (18%):</td>
                        <td class="value">{{ $document->moneda }} {{ number_format($document->mto_igv, 2) }}</td>
                    </tr>
                @endif
                
                @if($document->mto_isc > 0)
                    <tr>
                        <td class="label">ISC:</td>
                        <td class="value">{{ $document->moneda }} {{ number_format($document->mto_isc, 2) }}</td>
                    </tr>
                @endif
                
                @if($document->mto_icbper > 0)
                    <tr>
                        <td class="label">ICBPER:</td>
                        <td class="value">{{ $document->moneda }} {{ number_format($document->mto_icbper, 2) }}</td>
                    </tr>
                @endif
                
                @if($document->mto_otros_tributos > 0)
                    <tr>
                        <td class="label">Otros Tributos:</td>
                        <td class="value">{{ $document->moneda }} {{ number_format($document->mto_otros_tributos, 2) }}</td>
                    </tr>
                @endif
                
                @if($document->mto_anticipos > 0)
                    <tr>
                        <td class="label">Descuento Anticipos:</td>
                        <td class="value">-{{ $document->moneda }} {{ number_format($document->mto_anticipos, 2) }}</td>
                    </tr>
                @endif
                
                <tr class="total-final">
                    <td class="label">TOTAL:</td>
                    <td class="value">{{ $document->moneda }} {{ number_format($document->mto_imp_venta, 2) }}</td>
                </tr>
            </table>
        </div>
    </div>
@else
    {{-- Ticket Totals --}}
    <div class="totals-section">
        <table class="totals-table">
            @if($document->mto_oper_gravadas > 0)
                <tr>
                    <td class="label">Op. Gravadas:</td>
                    <td class="value">{{ number_format($document->mto_oper_gravadas, 2) }}</td>
                </tr>
            @endif
            
            @if($document->mto_oper_exoneradas > 0)
                <tr>
                    <td class="label">Op. Exoneradas:</td>
                    <td class="value">{{ number_format($document->mto_oper_exoneradas, 2) }}</td>
                </tr>
            @endif
            
            @if($document->mto_oper_inafectas > 0)
                <tr>
                    <td class="label">Op. Inafectas:</td>
                    <td class="value">{{ number_format($document->mto_oper_inafectas, 2) }}</td>
                </tr>
            @endif
            
            @if($document->mto_igv > 0)
                <tr>
                    <td class="label">IGV (18%):</td>
                    <td class="value">{{ number_format($document->mto_igv, 2) }}</td>
                </tr>
            @endif
            
            @if($document->mto_icbper > 0)
                <tr>
                    <td class="label">ICBPER:</td>
                    <td class="value">{{ number_format($document->mto_icbper, 2) }}</td>
                </tr>
            @endif
            
            <tr class="total-final">
                <td class="label">TOTAL:</td>
                <td class="value">{{ $document->moneda }} {{ number_format($document->mto_imp_venta, 2) }}</td>
            </tr>
        </table>
    </div>
@endif