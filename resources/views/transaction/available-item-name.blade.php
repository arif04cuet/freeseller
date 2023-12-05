@php
    $item = explode('#', $record->item_type);
    $orderNo = '<a href="' . route('filament.app.resources.orders.index', ['tableSearch' => $item[1]]) . '"><u>' . $item[1] . '</u></a>';
    $itemTitle = match ($item[0]) {
        'order' => $record->type == 'withdraw' ? 'Charges for Order #' . $orderNo : 'Order #' . $orderNo,
        'recharge' => 'Wallet Rechage',
        'fund' => 'Fund Withdrawal',
    };
@endphp

<div>
    <div>{!! $itemTitle !!}</div>
    <div>{{ \Carbon\Carbon::parse($record->date)->format('d-m-Y') }}</div>
</div>
