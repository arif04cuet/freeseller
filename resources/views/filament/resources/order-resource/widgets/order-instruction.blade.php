<x-filament-widgets::widget>

    <div class="flex justify-center rounded-lg">

        <div class="ml-8">

            <div class="bg-white w-full mx-auto ">
                <ul class="shadow-box">

                    <li class="relative p-4 " x-data="{ selected: null }">

                        <button type="button" class="w-full text-left"
                            @click="selected !== 1 ? selected = 1 : selected = null">
                            <div class="flex items-center justify-between">
                                <div class="font-bold">
                                    {{ __('messages.order_instruction') }}

                                </div>
                            </div>
                        </button>

                        <div class="relative overflow-hidden transition-all max-h-0 duration-700" style=""
                            x-ref="container1"
                            x-bind:style="selected == 1 ? 'max-height: ' + $refs.container1.scrollHeight + 'px' : ''">
                            <div class="mx-2  mt-2">
                                <ul class="list-disc list-inside">
                                    <li>{{ __('messages.order_instruction_1', ['min_balance' => config('freeseller.minimum_acount_balance')]) }}
                                        <a class="text-primary-500"
                                            href="{{ route('filament.app.resources.wallet-recharge-requests.index') }}">এখান
                                            থেকে ।
                                        </a>
                                    </li>
                                    <li>{{ __('messages.order_instruction_2', ['lock_amount' => $lock_amount]) }}
                                    </li>
                                    <li>{{ __('messages.order_instruction_3', [
                                        'total_charge' => $total_fee,
                                        'platform_fee' => $platform_fee,
                                        'cod_fee' => $cod_fee,
                                    ]) }}
                                    </li>
                                    <li>{{ __('messages.order_instruction_4', ['lock_amount' => $lock_amount]) }}
                                    </li>
                                    <li>{{ __('messages.order_instruction_5') }}</li>
                                </ul>
                            </div>
                        </div>

                    </li>




                </ul>
            </div>
        </div>

    </div>
    <style>
        .max-h-0 {
            max-height: 0;
        }
    </style>
</x-filament-widgets::widget>
