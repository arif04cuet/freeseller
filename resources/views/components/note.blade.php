<div class="pb-2">
    <div class="flex">
        <p>{{ $time }}</p>

        <span class = 'px-4' style="{{ $status == 'approved' ? 'color:green' : 'color:orange' }}">
            {{ $status }}
        </span>


        <div x-data="{ coppied: false }">
            <button
                @click.prevent="navigator.clipboard.writeText('{{ $note }}'),coppied = true, setTimeout(() => coppied = false, 1000)"
                title="Copy note">
                <x-filament::icon icon="heroicon-m-clipboard" class="h-5 w-5 text-gray-500 dark:text-gray-400" />
            </button>
            <span x-show="coppied" class="text-primary-400 text-sm">Coppied to Clipboard</span>
        </div>

    </div>

    <p>{{ $note }}</p>

</div>
