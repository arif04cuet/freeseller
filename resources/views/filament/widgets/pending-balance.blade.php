@php
    $columns = $this->getColumns();
@endphp

<x-filament-widgets::widget>

    <div @if ($pollingInterval = $this->getPollingInterval()) wire:poll.{{ $pollingInterval }} @endif @class([
        'fi-wi-stats-overview grid gap-6',
        'md:grid-cols-1' => $columns === 1,
        'md:grid-cols-2' => $columns === 2,
        'md:grid-cols-3' => $columns === 3,
        'md:grid-cols-2 xl:grid-cols-4' => $columns === 4,
    ])>



        <div
            class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="grid gap-y-2">
                <div class="flex items-center gap-x-2">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ $balance->getLabel() }}
                    </span>
                    @if ($balance->getValue())
                        {{ $this->listAction() }}
                    @endif
                </div>

                <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                    {{ $balance->getValue() }}
                </div>


                <div class="flex items-center gap-x-1">

                    <span class="fi-wi-stats-overview-stat-description text-sm text-custom-600 dark:text-custom-400"
                        style="--c-400:var(--warning-400);--c-600:var(--warning-600);">
                        {{ $balance->getDescription() }}
                    </span>
                </div>
            </div>

        </div>

    </div>
    <x-filament-actions::modals />
</x-filament-widgets::widget>
