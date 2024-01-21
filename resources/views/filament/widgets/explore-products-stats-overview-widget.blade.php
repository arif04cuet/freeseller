@php
    $columns = $this->getColumns();
@endphp

<x-filament-widgets::widget class="fi-wi-stats-overview">
    <div @class(['fi-wi-stats-overview-stats-ctn flex gap-4'])>
        @foreach ($this->getCachedStats() as $stat)
            {{ $stat }}
        @endforeach
    </div>
</x-filament-widgets::widget>
