<div class="fi-ta-text grid gap-y-1 px-3">
    <div>{{ $getRecord()->price }}</div>

    @if ($getRecord()->getOfferPrice())
        <del> {{ $getRecord()->getAttributes()['price'] }}</del>
    @endif
</div>
