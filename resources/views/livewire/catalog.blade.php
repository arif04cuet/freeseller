<div class="px-2 md:px-0">
    <div class="flex flex-col-reverse md:flex-row justify-between mb-2">

        <div class="py-2 flex flex-col md:flex-row gap-2 mb-2 md:gap-4 items-end ">

            <input class="w-full md:w-auto  border p-2 md:px-2" placeholder="Search" type="search"
                wire:model.live.debounce.1000ms="search">
            <div class="flex gap-4 items-center">

                @if ($this->canSeeWholesalers)
                    <select class=" w-1/3  md:w-auto border text-sm rounded-lg block p-2.5 bg-white"
                        wire:model.live="filters.wholesaler">
                        <option value="">Wholesalers</option>
                        @foreach ($this->wholesalers as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                @endif
                <select class="w-1/3  md:w-auto border text-sm rounded-lg block p-2.5 bg-white"
                    wire:model.live="filters.cat">
                    <option value="">Category</option>
                    @foreach ($this->categories as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>

                <select class="w-1/3  md:w-auto border text-sm rounded-lg block p-2.5 bg-white"
                    wire:model.live="filters.color">
                    <option value="">Color</option>
                    @foreach ($this->colors as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>

                <select class=" w-1/3 md:w-auto border text-sm rounded-lg block p-2.5 bg-white " wire:model.live="sort">
                    <option value="">Sort by</option>
                    <option value="stock">Stock quantity (High to Low)</option>
                    <option value="stock_low">Stock quantity (Low to high)</option>
                    <option value="price">Price Low to high</option>
                    <option value="sales">Most sales products</option>
                    <option value="new">New products</option>
                </select>
                <x-filament::icon-button icon="heroicon-m-arrow-path" wire:click="resetAll" label="Reset"
                    class="w-1/3" />
            </div>

        </div>
        <div class="mb-2 ">
            @livewire(\App\Filament\Resources\ExploreProductsResource\Widgets\CatalogOverview::class)
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @if ($productsCount = $products->count())
            @foreach ($products as $product)
                <x-product :product="$product" />
            @endforeach
        @else
            No products found.
        @endif
    </div>
    @if ($this->total > $productsCount)
        <div class="self-center loading loading-spinner text-center" x-intersect="$wire.loadMore()">
            <x-filament::loading-indicator class="h-8 w-8 mx-auto" />
        </div>
    @endif

</div>
