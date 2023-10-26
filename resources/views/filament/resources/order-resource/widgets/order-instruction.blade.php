<x-filament-widgets::widget>
    <x-filament::section>
        <div class="font-bold mb-2">
            {{ __('messages.order_instruction') }}

        </div>
        <div class="mx-2">
            <ul class="list-disc">
                <li>{{ __('messages.order_instruction_1') }}
                    <a class="text-primary-500"
                        href="{{ route('filament.app.resources.wallet-recharge-requests.index') }}">এখান থেকে ।
                    </a>
                </li>
                <li>{{ __('messages.order_instruction_2') }}</li>
                <li>{{ __('messages.order_instruction_3') }}</li>
                <li>{{ __('messages.order_instruction_4') }}</li>
                <li>{{ __('messages.order_instruction_5') }}</li>
            </ul>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
