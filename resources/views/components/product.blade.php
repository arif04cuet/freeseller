<div class="max-w-sm bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
    <a class="text-center" wire:navigate href="{{ route('product', ['product' => $product]) }}">
        @if ($image = $product->getMedia('sharees')?->first()?->getUrl('thumb'))
            <img loading="lazy" class="rounded-t-lg mx-auto" src="{{ $image }}" alt="{{ $product->name }}" />
        @else
            <svg fill="#000000" width="w-full" viewBox="0 0 32 32" id="icon" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <style>
                        .cls-1 {
                            fill: none;
                        }
                    </style>
                </defs>
                <title>no-image</title>
                <path
                    d="M30,3.4141,28.5859,2,2,28.5859,3.4141,30l2-2H26a2.0027,2.0027,0,0,0,2-2V5.4141ZM26,26H7.4141l7.7929-7.793,2.3788,2.3787a2,2,0,0,0,2.8284,0L22,19l4,3.9973Zm0-5.8318-2.5858-2.5859a2,2,0,0,0-2.8284,0L19,19.1682l-2.377-2.3771L26,7.4141Z" />
                <path
                    d="M6,22V19l5-4.9966,1.3733,1.3733,1.4159-1.416-1.375-1.375a2,2,0,0,0-2.8284,0L6,16.1716V6H22V4H6A2.002,2.002,0,0,0,4,6V22Z" />
                <rect id="_Transparent_Rectangle_" data-name="&lt;Transparent Rectangle&gt;" class="cls-1"
                    width="32" height="32" />
            </svg>
        @endif

    </a>
    <div class="p-3 md:p-4">
        <h5 class="mb-2 text-normal tracking-tight text-gray-900 dark:text-white flex justify-between">
            <a wire:navigate href="{{ route('product', ['product' => $product]) }}">
                {{ \Illuminate\Support\Str::limit($product->name, 35, $end = '..') }}
            </a>
            <span>{{ $product->skus_sum_quantity }}</span>
        </h5>
        <div class="mb-2 flex justify-between">
            <div>
                @auth
                    <x-product.price :product="$product" />
                @else
                    <span class="text-yellow-600">মূল্য দেখতে লগইন করুন</span>
                @endauth
            </div>
            <div>{{ $product->category->name }}</div>
        </div>
        <div style="--col-span-default: span 1 / span 1;" class="col-[--col-span-default] flex-1 w-full">

            <div class="fi-ta-col-wrp">
                <div class="flex w-full disabled:pointer-events-none justify-start text-start">
                    <div class="fi-ta-image">
                        <div class="flex items-center gap-x-2.5">
                            <div class="flex -space-x-2 flex-wrap">
                                @foreach ($product->skus as $sku)
                                    @if ($image = $sku->getMedia('sharees')?->first()?->getUrl('thumb'))
                                        <img src="{{ $image }}" style="height: 2rem; width: 2rem;"
                                            class="max-w-none object-cover object-center rounded-full ring-white dark:ring-gray-900 ring-2">
                                    @endif
                                @endforeach

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
