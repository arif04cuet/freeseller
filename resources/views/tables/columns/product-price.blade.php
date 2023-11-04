<div class="div">
    {{-- @if (!is_null($getLabel()))
        <div>{{ $getLabel() }}</div>
    @endif --}}
    <div>{{ $getRecord()->price }}</div>

    @if ($getRecord()->getOfferPrice())
        <del> <span class="text-sm"> {{ $getRecord()->getAttributes()['price'] }} </span> </del>
    @endif
</div>
