<x-filament-panels::page>

    <div>

        <input wire:model.live="from" type="date" name="from">
        <input wire:model.live="to" type="date" name="to">
        <button type="button" class="fi-btn ">Submit</button>
    </div>

    <div>
        {{ $from }}
        {{ $to }}
    </div>
</x-filament-panels::page>
