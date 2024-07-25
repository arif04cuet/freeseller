<div class="">
    Customer: {{ $record->customer->name }} <br />
    CN: {{ $record->consignment_id ?? '' }}
</div>

@if ($courierMsg = $record->note_for_courier)
    <x-note :time="$record->created_at->format('d/m/Y H:i:s')" status="approved" :note="$courierMsg" />
@endif

@if ($record->notes)
    @foreach ($record->notes as $note)
        <x-note :cn="$record->consignment_id" :time="$record->created_at->format('d/m/Y H:i:s')" :status="$note['status'] ?? 'pending'" :note="$note['text']" />
    @endforeach
@endif
