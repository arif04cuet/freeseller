<div class="div flex gap-2">
    <div>{{ $product->price }}</div>

    @if ($product->getOfferPrice())
        <div>
            <del> <span class="text-sm"> {{ (int) $product->getAttributes()['price'] }} </span> </del>
        </div>
    @endif
</div>
