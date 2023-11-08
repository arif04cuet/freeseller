<p class="font-bold">Payment Channel Info</p>
<p>Type: {{ $getRecord()->paymentChannel->type }}</p>

@if ($getRecord()->paymentChannel->type == \App\Enum\PaymentChannel::Bank)
    <p>Bank Name: {{ $getRecord()->paymentChannel->bank_name }}</p>
    <p>A/C Name: {{ $getRecord()->paymentChannel->account_name }}</p>
    <p>A/C Number: {{ $getRecord()->paymentChannel->account_number }}</p>
    <p>Routing Number: {{ $getRecord()->paymentChannel->bank_routing_no }}</p>
@else
    <p>Mobile Number : {{ $getRecord()->paymentChannel->mobile }}</p>
@endif
