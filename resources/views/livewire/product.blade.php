<div class="bg-gray-100 dark:bg-gray-800 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-2">
        <div class="flex flex-col md:flex-row -mx-4">
            <div class="md:flex-1 px-4">


                <div id="custom-controls-gallery" class="relative w-full" data-carousel="slide">
                    <!-- Carousel wrapper -->
                    <div class="relative h-56 overflow-hidden rounded-lg md:h-96">

                        @foreach ($product->skus as $sku)
                            @foreach ($sku->getMedia('sharees') as $media)
                                <div class="hidden duration-700 ease-in-out" data-carousel-item>
                                    <img loading="lazy" src="{{ $media->getUrl() }}"
                                        class="absolute block max-w-full h-auto -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2"
                                        alt="{{ $product->name }}">
                                </div>
                            @endforeach
                        @endforeach


                    </div>
                    <div class="flex justify-center items-center pt-4">
                        <button type="button"
                            class="flex justify-center items-center me-4 h-full cursor-pointer group focus:outline-none"
                            data-carousel-prev>
                            <span
                                class="text-gray-400 hover:text-gray-900 dark:hover:text-white group-focus:text-gray-900 dark:group-focus:text-white">
                                <svg class="rtl:rotate-180 w-5 h-5" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="M13 5H1m0 0 4 4M1 5l4-4" />
                                </svg>
                                <span class="sr-only">Previous</span>
                            </span>
                        </button>
                        <button type="button"
                            class="flex justify-center items-center h-full cursor-pointer group focus:outline-none"
                            data-carousel-next>
                            <span
                                class="text-gray-400 hover:text-gray-900 dark:hover:text-white group-focus:text-gray-900 dark:group-focus:text-white">
                                <svg class="rtl:rotate-180 w-5 h-5" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9" />
                                </svg>
                                <span class="sr-only">Next</span>
                            </span>
                        </button>
                    </div>
                </div>


            </div>
            <div class="md:flex-1 px-4">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">{{ $product->name }}</h2>
                <p class="text-gray-600 dark:text-gray-300 text-sm mb-4">
                    {!! $product->description !!}
                </p>
                <div class="flex mb-4 mt-4 justify-between">
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
                            {{ $product->skus->sum('quantity') }}</span>
                    </div>
                </div>
                {{-- <div class="mb-4">
                    <span class="font-bold text-gray-700 dark:text-gray-300">কালার:</span>
                    <div class="flex items-center mt-2">
                        <button class="w-6 h-6 rounded-full bg-gray-800 dark:bg-gray-200 mr-2"></button>
                        <button class="w-6 h-6 rounded-full bg-red-500 dark:bg-red-700 mr-2"></button>
                        <button class="w-6 h-6 rounded-full bg-blue-500 dark:bg-blue-700 mr-2"></button>
                        <button class="w-6 h-6 rounded-full bg-yellow-500 dark:bg-yellow-700 mr-2"></button>
                    </div>
                </div>
                <div class="flex -mx-2 mb-4">
                    <div class="w-1/2 px-2">
                        <button
                            class="w-full bg-gray-900 dark:bg-gray-600 text-white py-2 px-4 rounded-full font-bold hover:bg-gray-800 dark:hover:bg-gray-700">
                            Add
                            to Cart</button>
                    </div>

                </div> --}}
            </div>
        </div>
    </div>
</div>
