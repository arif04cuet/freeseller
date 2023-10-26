<x-filament-widgets::widget>
    <x-filament::section>
        <div class="mb-2">First sent payment to bank/bkash. then create recharge request with
            transaction id & receipt.
        </div>
        <hr />
        <div class="mt-2 flex justify-between flex-col md:flex-row gap-4">
            <div>
                <strong> Bank Information</strong>
                <p><strong>Bank Name</strong> : Bank Asia Limited</p>
                <p><strong>Branch</strong> : Gulshan Branch</p>
                <p><strong>A/C Name</strong> : Arif Hossain</p>
                <p>
                    <strong>A/C No.</strong> : 00434030848
                </p>

            </div>

            <div>
                <strong> bKash</strong>
                <p>01717348147</p>
                {{-- <p>{{ config('freeseller.platform_bkash') }}</p> --}}
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
