<div class="div flex gap-2">
    <div>
        <span>৳</span>
        {{ $product->price }}
    </div>

    @if ($product->getOfferPrice())
        <div>
            <del> <span class=""> {{ (int) $product->getAttributes()['price'] }} </span> </del>
        </div>
    @endif
</div>
