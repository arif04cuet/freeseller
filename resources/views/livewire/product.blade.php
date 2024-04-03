<div class="py-8" x-data="{ selectedImg: @entangle('selectedImg') }">



    <div class="mx-auto px-4 sm:px-6 lg:px-2">
        <div class="flex flex-col md:flex-row -mx-4">
            <div class="md:flex-1 px-4 mb-4">

                <div class="grid gap-4">
                    <div class="h-96">
                        <span class="" x-show="!selectedImg">
                            <x-filament::loading-indicator class="h-8 w-8 mx-auto" />
                        </span>
                        <img x-show="selectedImg" class="object-cover h-full mx-auto max-w-full rounded-lg"
                            :src="selectedImg" alt="">
                    </div>

                    <div wire:ignore class="grid grid-cols-5 md:grid-cols-7 gap-2" wire:ignore>

                        @php
                            $m = 0;
                            $s = 0;
                            $product = $this->product;
                        @endphp
                        @foreach ($product->skus as $sku)
                            @foreach ($sku->getMedia('sharees') as $media)
                                @php
                                    if (!$m) {
                                        $m = $media->id;
                                        $s = $sku->id;
                                    }
                                @endphp
                                <div wire:key="{{ $media->id }}" class="h-14 w-14">
                                    <img @click="selectedImg=null"
                                        wire:click="loadImg({{ $sku->id }}, {{ $media->id }})" loading="lazy"
                                        class="h-full object-cover cursor-pointer w-full rounded-lg"
                                        src="{{ $media->getUrl() }}" alt="{{ $product->name }}" />
                                </div>
                            @endforeach
                        @endforeach

                        <span x-init="$wire.loadImg({{ $s }}, {{ $m }})"></span>

                    </div>
                </div>
            </div>
            <div class="md:flex-1 px-4">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">
                    {{ $product->name }}
                </h2>
                <div class="flex items-center justify-between">

                    @if (auth()->check() && auth()->user()->canPlaceOrder() && $this->lists)
                        <div class="flex ">
                            <select wire:model.live="listId" wire:confirm="Are you sure?"
                                class="focus:outline-none border text-sm rounded-lg block p-2.5 bg-white">
                                <option value="" class="">Add to list
                                </option>
                                @foreach ($this->lists as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <x-share-buttons :title="$product->name" />
                </div>
                <p class="text-gray-600 dark:text-gray-300 text-sm mb-4">
                    {!! $product->description !!}
                </p>
                <div class="flex mb-4 mt-4 gap-8">
                    <div class="mr-4 flex gap-4">
                        <span class="font-bold text-gray-700 dark:text-gray-300">দাম:</span>
                        <span class="text-gray-600 dark:text-gray-300 text-lg">
                            @auth
                                <x-product.price :product="$product" />
                            @endauth
                        </span>
                    </div>
                    <div>
                        <span class="font-bold text-gray-700 dark:text-gray-300"> কালার:
                            {{ $product->skus->count() }}
                        </span>
                    </div>
                    <div>
                        <span class="font-bold text-gray-700 dark:text-gray-300"> স্টক:
                            {{ $stock }}
                        </span>
                    </div>
                </div>

                @auth
                    @if ($stock)
                        <div x-data="{ show: false }" class="flex -mx-2 mb-4">
                            <input type="number" wire:model.live="quantity" min="1" max="{{ $stock }}"
                                class="border border-black rounded-lg w-1/4 px-2">
                            <div class="w-1/2 px-2" x-show="!show">
                                <button
                                    @click="$dispatch('productAddedToCart');show = true;setTimeout(() => show = false, 5000)"
                                    wire:click="addToCart"
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
                @else
                    <span class="text-yellow-600">মূল্য দেখতে লগইন করুন</span>
                @endauth
            </div>
        </div>
    </div>
</div>
