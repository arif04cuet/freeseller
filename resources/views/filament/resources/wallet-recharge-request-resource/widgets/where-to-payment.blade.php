<x-filament-widgets::widget>
    <x-filament::section>
        <div class="mb-2">{{ __('messages.recharge_instruction') }}</div>
        <hr />
        <div class="mt-2 flex justify-between flex-col md:flex-row gap-4">
            <div>
                <strong> Bank Information</strong>
                <p><strong>Bank Name</strong> : Southeast Bank PLC</p>
                <p><strong>Branch</strong> : Balla Branch</p>
                <p><strong>A/C Name</strong> : FREE SELLER</p>
                <p>
                    <strong>A/C No.</strong> : 010911100000484
                </p>

            </div>

            <div>
                <strong> bKash/Nagad (Personal)</strong>
                <p>01717348147</p>
                {{-- <p>{{ config('freeseller.platform_bkash') }}</p> --}}
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
