@if ($courierMsg = $record->note_for_courier)
    <div class="pb-2">
        <div class="flex">
            <p>{{ $record->created_at->format('d/m/Y H:i:s') }}</p>
            <span style="color: green;" class="px-4">approved</span>
        </div>

        <p>{{ $courierMsg }}</p>

    </div>
@endif

@if ($record->notes)
    @foreach ($record->notes as $note)
        <div class="pb-2">
            <div class="flex">
                <p>{{ $note['time'] ?? '' }}</p>
                <span class = 'px-4'
                    style="{{ isset($note['status']) && $note['status'] == 'approved' ? 'color:green' : 'color:orange' }}">
                    {{ $note['status'] ?? 'pending' }}</span>
            </div>
            <p>{{ $note['text'] ?? '' }}</p>
        </div>
    @endforeach

@endif
