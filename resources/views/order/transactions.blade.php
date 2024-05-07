<div>
    @livewire(
        'order-transactions',
        [
            'order' => $order,
        ],
        key($order->id)
    )
</div>
