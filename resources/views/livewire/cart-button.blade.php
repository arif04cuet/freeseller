<div x-data>


    <x-filament::button type="button" wire:click="openCart"
        class="relative inline-flex items-center p-3 text-sm font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
        icon="heroicon-o-shopping-cart">
        <x-slot name="badge">
            <div
                class="absolute inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-red-500 border-2 border-white rounded-full -top-2 -end-2 dark:border-gray-900">
                {{ $product_count }}
            </div>
        </x-slot>
    </x-filament::button>

</div>
