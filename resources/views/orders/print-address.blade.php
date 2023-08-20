@extends('layout.print')

@php
    $reseller = $order->reseller;
    $address = $reseller->address;
    $business = $reseller->business->first();
    $customer = $order->customer;
@endphp

@section('content')
    <div class="mx-auto p-16" style="max-width: 800px;">

        <div class="flex justify-between mb-8 px-5">
            <div>
                <h4 class="text-xl"> {{ $business->name }}</h4>
                <p>{{ $address->address }}</p>

            </div>
            <div class="">
                <h4 class="text-xl"> {{ $customer->name }}</h4>
                <p>{{ $customer->address }}</p>

            </div>
        </div>


    </div>

    <script>
        window.print();
    </script>
@endsection
