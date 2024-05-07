<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    @if ($productsCount = $products->count())
        @foreach ($products as $product)
            <x-product :product="$product" />
        @endforeach
    @else
        No products found.
    @endif
</div>
{{-- @if ($this->total > $productsCount)
    <div class="self-center loading loading-spinner text-center" x-intersect="$wire.loadMore()">
        <x-filament::loading-indicator class="h-8 w-8 mx-auto" />
    </div>
@endif --}}
