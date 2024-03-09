<div x-data>

    <button type="button" @click="$dispatch('show-cart')"
        class="relative inline-flex items-center p-3 text-sm font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
        <x-filament::icon icon="heroicon-o-shopping-cart" class="h-5 w-5 text-white dark:text-gray-400" />
        <div
            class="absolute inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-red-500 border-2 border-white rounded-full -top-2 -end-2 dark:border-gray-900">
            {{ $product_count }}
        </div>
    </button>

</div>
