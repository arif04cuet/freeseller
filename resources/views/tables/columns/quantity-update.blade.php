@php
    $name = 'skus.' . $getRecord()->id;
    $status = 'updatedSkus.' . $getRecord()->id;

@endphp
<div>
    <input min="0" oninput="validity.valid||(value='');" placeholder="update new qnt" type="number"
        wire:model.lazy="{{ $name . '.qnt' }}" />
    @if (isset($this->updatedSkus[$getRecord()->id]['status']))
        saved
    @endif
</div>
