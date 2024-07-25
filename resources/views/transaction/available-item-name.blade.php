@php

    $item = explode('#', $record->item_type);
    $recordId = $item[1];
    $orderNo =
        '<a href="' .
        route('filament.app.resources.orders.index', ['tableSearch' => $recordId]) .
        '"><u>' .
        $recordId .
        '</u></a>';
    $itemTitle = match ($item[0]) {
        'order' => $record->type == 'withdraw' ? 'Charges for Order #' . $orderNo : 'Order #' . $orderNo,
        'recharge' => 'Wallet Rechage',
        'recharge_fee' => 'Rechage Fee for #' .
            '<a href="' .
            route('filament.app.resources.wallet-recharge-requests.index', ['tableSearch' => $recordId]) .
            '"><u>' .
            $recordId .
            '</u></a>',
        'fund' => 'Fund Withdrawal',
        'claim' => 'Order Claim ' .
            (auth()->user()->isWholesaler()
                ? ''
                : '# <a href="' .
                    route('filament.app.resources.order-claims.index', ['tableSearch' => $recordId]) .
                    '"><u>' .
                    $recordId .
                    '</u></a>'),
    };
@endphp

<div>
    <div>{!! $itemTitle !!}</div>
    <div>{{ \Carbon\Carbon::parse($record->date)->format('d-m-Y') }}</div>
</div>
