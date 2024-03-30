<div class="px-2 md:px-0">

    <div class="py-2 flex flex-col md:flex-row gap-2 mb-2 md:gap-4 ">

        <input class="md:w-auto  border p-2 md:px-2" placeholder="Search" type="search"
            wire:model.live.debounce.1000ms="search">
        <div class="flex gap-4 items-center">
            <select class=" w-1/2  md:w-auto border text-sm rounded-lg block p-2.5 bg-white"
                wire:model.live="filters.cat">
                <option value="">Select Category</option>
                @foreach ($this->categories as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>

            <select class=" w-1/2 md:w-auto border text-sm rounded-lg block p-2.5 bg-white " wire:model.live="sort">
                <option value="">Sort by</option>
                <option value="price">Price Low to high</option>
                <option value="sales">Most sales products</option>
                <option value="new">New products</option>
            </select>
            <x-filament::icon-button icon="heroicon-m-arrow-path" wire:click="resetAll" label="Reset" />
        </div>

    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @if ($products->count())
            @foreach ($products as $product)
                <x-product :product="$product" />
            @endforeach
        @else
            No products found.
        @endif
    </div>

    <div class="my-2">

        {{ $products->links() }}
    </div>
</div>
