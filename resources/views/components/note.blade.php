@php
    $cn = $cn ?? '';
@endphp
<div class="pb-2">
    <div class="flex">
        <p>{{ $time }}</p>

        <span class = 'px-4' style="{{ $status == 'approved' ? 'color:green' : 'color:orange' }}">
            {{ $status }}
        </span>


        @if (!auth()->user()->isReseller() && $status == 'pending')
            <div x-data="{
                coppied: false,
                copyAndRedirect() {
                    this.coppied = false;
                    window.open('https://steadfast.com.bd/user/edit-parcel/{{ $cn }}', '_blank');
                }
            }">
                <button
                    @click.prevent="navigator.clipboard.writeText('{{ str_replace("'", '', $note) }}'),coppied = true, setTimeout(() => copyAndRedirect(), 1000)"
                    title="Copy note">
                    <x-filament::icon icon="heroicon-m-clipboard" class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                </button>
                <span x-show="coppied" class="text-primary-400 text-sm">Coppied to Clipboard</span>
            </div>
        @endif

    </div>

    <p>{{ $note }}</p>

</div>
