{{-- PDF Dispatch Observations Component --}}
{{-- Props: $observaciones, $format --}}

@if(in_array($format, ['a4', 'A4', 'a5', 'A5']))
    {{-- A4/A5 Observations --}}
    <div class="observations-section">
        <div class="observations-header">
            <h4>OBSERVACIONES</h4>
        </div>
        <div class="observations-content">
            <p>{{ $observaciones }}</p>
        </div>
    </div>
@else
    {{-- Ticket Observations --}}
    <div class="observations-ticket">
        <div class="obs-header"><strong>OBSERVACIONES:</strong></div>
        <div class="obs-content">{{ $observaciones }}</div>
    </div>
@endif