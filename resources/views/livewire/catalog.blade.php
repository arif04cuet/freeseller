<div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach ($products as $product)
            <x-product :product="$product" />
        @endforeach
    </div>

    <div class="my-2">

        {{ $products->links() }}
    </div>
</div>
