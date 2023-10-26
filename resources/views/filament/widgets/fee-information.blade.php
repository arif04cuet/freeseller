<x-filament-widgets::widget>

    <div
        class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">

        <div class="font-bold">FreeSeller Charges</div>
        <hr class="my-2" />
        <ul>
            <li>COD Charge: {{ config('freeseller.cod_fee') }} %</li>
            <li>Platform Fee: {{ config('freeseller.platform_fee') }} %</li>
        </ul>
        {{-- <p class="mt-2 text-sm italic">* FreeSeller can changes charge any time</p> --}}
    </div>

</x-filament-widgets::widget>
