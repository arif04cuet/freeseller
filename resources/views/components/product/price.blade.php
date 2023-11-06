<div class="div">
    <div>{{ $product->price }}</div>

    @if ($product->getOfferPrice())
        <del> <span class="text-sm"> {{ $product->getAttributes()['price'] }} </span> </del>
    @endif
</div>
