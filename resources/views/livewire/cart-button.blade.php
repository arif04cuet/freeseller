<div x-data>


    <x-filament::icon-button size="lg" type="button" wire:click="openCart" class="relative inline-flex items-center "
        icon="heroicon-o-shopping-cart">
        <x-slot name="badge">
            <div
                class="absolute inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 border-2 border-white rounded-full -top-2 -end-2 dark:border-gray-900">
                {{ $product_count }}
            </div>
        </x-slot>
    </x-filament::icon-button>

</div>
