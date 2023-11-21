<div>{{ $order->customer->mobile }}</div>
@if ($email = $order->customer->email)
    <div>{{ $email }}</div>
@endif
<div>{{ $order->customer->address }}</div>
