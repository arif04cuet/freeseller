<div>
    {{-- Similar Products --}}
    @if ($this->similarProducts->count())

        <div>
            <h2 class="text-xl font-bold">Similar Products</h2>
            <hr class="py-2">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

                @foreach ($this->similarProducts as $product)
                    <x-product :product="$product" />
                @endforeach

            </div>
        </div>
    @endif
</div>
