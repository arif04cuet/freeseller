@extends('layout.print')

@php
    $reseller = $order->reseller;
    $address = $reseller->address;
    $business = $reseller->business;
    $customer = $order->customer;
@endphp

@section('content')
    <div class="mx-auto p-16" style="max-width: 800px;">
        <div class="mb-8 ">Parcel ID# {{ $order->consignment_id }}</div>
        <div class="flex justify-between mb-8">
            <div class="flex gap-2 items-start">
                @if ($logo = $business->logo)
                    <img class="w-14 " src="/storage/{{ $logo }}" alt="" srcset="">
                @endif
                <div>
                    <h4 class="text-xl"> {{ $business->name }}</h4>
                    @if ($slogan = $business->slogan)
                        <p class="text-sm italic mb-2">
                            {{ $slogan }}
                        </p>
                    @endif
                    <p>{{ $address->address }}</p>
                </div>

            </div>
            <div class="">
                <h4 class="text-xl"> {{ $customer->name }}</h4>
                <p>{{ $customer->mobile }}</p>
                <p>{{ $customer->address }}</p>

            </div>
        </div>



        <div class="max-w-5xl mx-auto py-2 bg-white">
            <article class="overflow-hidden">
                <div class="bg-[white] rounded-b-md">

                    <div class="flex flex-col mx-0 mt-8">
                        <table class="min-w-full divide-y divide-slate-500">
                            <thead>
                                <tr>
                                    <th scope="col"
                                        class="py-3.5 pl-4 pr-3 text-left text-sm font-normal text-slate-700 sm:pl-6 md:pl-0">
                                        Item
                                    </th>
                                    <th scope="col"
                                        class=" py-3.5 px-3 text-right text-sm font-normal text-slate-700 sm:table-cell">
                                        Quantity
                                    </th>
                                    <th scope="col"
                                        class=" py-3.5 px-3 text-right text-sm font-normal text-slate-700 sm:table-cell">
                                        Price (Taka)
                                    </th>
                                    <th scope="col"
                                        class="py-3.5 pl-3 pr-4 text-right text-sm font-normal text-slate-700 sm:pr-6 md:pr-0">
                                        Amount (Taka)
                                    </th>
                                </tr>
                            </thead>
                            <tbody>

                                @foreach ($order->items as $item)
                                    <tr class="border-b border-slate-200">
                                        <td class="py-4 pl-4 pr-3 text-sm sm:pl-6 md:pl-0">
                                            <div class="font-medium text-slate-700">{{ $item->sku->name }}</div>
                                        </td>
                                        <td class=" px-3 py-4 text-sm text-right text-slate-500 sm:table-cell">
                                            {{ $item->quantity }}
                                        </td>
                                        <td class="px-3 py-4 text-sm text-right text-slate-500 sm:table-cell">
                                            {{ $item->reseller_price }}
                                        </td>
                                        <td class="py-4 pl-3 pr-4 text-sm text-right text-slate-500 sm:pr-6 md:pr-0">
                                            {{ $item->total_amount }}
                                        </td>
                                    </tr>
                                @endforeach

                            </tbody>
                            <tfoot>
                                <tr>
                                    <th scope="row" colspan="3"
                                        class="pt-6 pl-6 pr-3 text-sm font-bold text-right text-slate-500 sm:table-cell md:pl-0">
                                        COD
                                    </th>

                                    <td class="pt-6 pl-3 pr-4 text-sm font-bold text-right text-slate-500 sm:pr-6 md:pr-0">
                                        {{ $order->cod }}
                                    </td>
                                </tr>

                            </tfoot>
                        </table>
                    </div>


                </div>
            </article>
        </div>

    </div>
    <script>
        window.print();
    </script>
@endsection
