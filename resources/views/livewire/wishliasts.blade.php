@php
    $skus = $this->items;
@endphp
<div class="px-2 md:px-0">

    <div class="py-2 flex flex-col md:flex-row gap-2 mb-2 md:gap-4 ">

        {{-- <input class="md:w-auto  border p-2 md:px-2" placeholder="Search" type="search"
            wire:model.live.debounce.1000ms="search"> --}}
        <h2 class="text-2xl font-bold">My Wishlist</h2>
        <hr>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @if ($productsCount = $skus->count())
            @foreach ($skus as $sku)
                <x-sku :sku="$sku" :key="$sku->id">
                    <x-slot:actions>
                        @if ($stock = $sku)
                            <div x-data="{ show: false }" class="flex -mx-2 mb-4">

                                <div class="w-full px-4" x-show="!show">
                                    <button
                                        @click="$dispatch('productAddedToCart');show = true;setTimeout(() => show = false, 5000)"
                                        wire:click="addToCart({{ $sku->id }})"
                                        class="w-full bg-blue-700 dark:bg-blue-800 text-white py-2 px-4 rounded-full font-bold hover:bg-blue-800 dark:hover:bg-blue-700">
                                        Add to Cart
                                    </button>
                                </div>

                                <div class="w-1/2 px-2" style="display: none" x-transition x-show="show">
                                    <div class="alert alert-success">
                                        <x-flash title="Product Added." />
                                    </div>
                                </div>

                            </div>
                        @else
                            <span class="text-yellow-600">স্টক নেই</span>
                        @endif

                    </x-slot>
                </x-sku>
            @endforeach
        @else
            No wishlist products found.
        @endif
    </div>


</div>
