<div class="flex py-6">
    <div class="h-24 w-24 flex-shrink-0 overflow-hidden rounded-md border border-gray-200">

        <img src="{{ $image ?? '' }}" alt="{{ $name }}" class="h-full w-full object-cover object-center">
    </div>

    <div class="ml-4 flex flex-1 flex-col">
        <div>
            <div class="flex justify-between text-base font-medium text-gray-900">
                <h3>
                    <a wire:click="redirectToProduct({{ $id }})" href="#">{{ $name }}</a>
                </h3>
                <p class="ml-4">{{ $price }}</p>
            </div>
            <p class="mt-1 text-sm text-gray-500"></p>
        </div>
        <div class="flex flex-1 items-end justify-between text-sm">
            <p class="text-gray-500 flex gap-4">
                <span>Qty</span>

                <x-filament::icon-button size="xs" type="button"
                    wire:click="updateCartItem({{ $id }},'minus')"
                    class="px-2 rounded hover:bg-gray-400 bg-gray-500 text-white" icon="heroicon-m-minus" />
                <span>{{ $quantity }}</span>
                <x-filament::icon-button size="xs" type="button"
                    wire:click="updateCartItem({{ $id }},'plus')"
                    class="px-2 rounded hover:bg-gray-400 bg-gray-500 text-white" icon="heroicon-m-plus" />
            </p>

            <div class="flex">
                <x-filament::icon-button type="button" wire:click="removeFromCart({{ $id }})" label="Remove"
                    icon="heroicon-m-x-mark" class="relative -m-2 p-2 text-gray-400 hover:text-gray-500" />
            </div>


        </div>
    </div>
</div>
