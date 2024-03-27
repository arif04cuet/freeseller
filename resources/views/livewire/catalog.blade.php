<div class="px-2 md:px-0">

    <div class="py-2 flex gap-4 ">

        <input class="w-1/2 md:w-auto  border px-2" placeholder="Search" type="text" wire:model.live="search">
        <select class="w-1/2  md:w-auto border text-sm rounded-lg block p-2.5" wire:model.live="filters.cat">
            <option value="">Select</option>
            @foreach ($this->categories as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach ($products as $product)
            <x-product :product="$product" />
        @endforeach
    </div>

    <div class="my-2">

        {{ $products->links() }}
    </div>
</div>
