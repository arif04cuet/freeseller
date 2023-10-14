@if (auth()->user()->isReseller())
    @livewire('pending-balance-list-for-reseller')
@endif

@if (auth()->user()->isWholesaler())
    @livewire('pending-balance-list-for-wholesaler')
@endif
