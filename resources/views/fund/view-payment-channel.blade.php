<p class="font-bold">Payment Channel Info</p>
<p>
    For:
    {{ $getRecord()->user->business->name . '- ' . $getRecord()->user->id_number . '-' . $getRecord()->user->name }}
</p>

<p>Type: {{ $getRecord()->paymentChannel->type }}</p>

@if ($getRecord()->paymentChannel->type == \App\Enum\PaymentChannel::Bank)
    <p>Bank Name: {{ $getRecord()->paymentChannel->bank_name }}</p>
    <p>A/C Name: {{ $getRecord()->paymentChannel->account_name }}</p>
    <p>A/C Number: {{ $getRecord()->paymentChannel->account_number }}</p>
    <p>Branch Name: {{ $getRecord()->paymentChannel->branch_name }}</p>
    <p>Routing Number: {{ $getRecord()->paymentChannel->bank_routing_no }}</p>
@else
    <p>Mobile Number : {{ $getRecord()->paymentChannel->mobile_no }}</p>
@endif
