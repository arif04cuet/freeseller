@extends('layout.print')
@php
    $totalCod = 0;
    $totalItems = 0;
@endphp
@section('content')
    <div>

        <div class="mx-auto p-4 max-w-3xl">
            <div class="mb-4 flex justify-between font-bold">
                <div>Date# {{ date('d-m-Y') }}</div>
                <div>Merchant ID# {{ config('services.steadfast.merchant_id') }}</div>

            </div>

            <div class="max-w-5xl mx-auto py-2 bg-white">

                <div class="flex flex-col mx-0 mt-4">
                    <table class="min-w-full divide-y divide-slate-500">
                        <thead>
                            <tr>
                                <th scope="col"
                                    class="py-3.5 pl-4 pr-3 text-left text-sm font-normal text-slate-700 sm:pl-6 md:pl-0">
                                    Order #
                                </th>
                                <th scope="col"
                                    class=" py-3.5 px-3 text-center text-sm font-normal text-slate-700 sm:table-cell">
                                    CN #
                                </th>
                                <th scope="col"
                                    class=" py-3.5 px-3 text-center text-sm font-normal text-slate-700 sm:table-cell">
                                    COD
                                </th>
                                <th scope="col"
                                    class=" py-3.5 px-3 text-center text-sm font-normal text-slate-700 sm:table-cell">
                                    Customer
                                </th>
                                <th scope="col"
                                    class=" py-3.5 px-3 text-right text-sm font-normal text-slate-700 sm:table-cell">
                                    Reseller
                                </th>
                            </tr>
                        </thead>
                        <tbody>

                            @foreach ($orders as $order)
                                @php
                                    $reseller = $order->reseller;
                                    $totalCod += $order->cod;
                                    $totalItems += $order->items->sum('quantity');
                                @endphp
                                <tr class="border-b border-slate-200">
                                    <td class="py-2 pl-4 pr-3 text-sm sm:pl-6 md:pl-0">
                                        <div class="font-medium text-slate-700">{{ $order->id }}</div>
                                    </td>
                                    <td class=" px-3 py-2 text-sm text-center text-slate-500 sm:table-cell">
                                        {{ $order->consignment_id }}
                                    </td>

                                    </td>
                                    <td class=" px-3 py-2 text-sm text-center text-slate-500 sm:table-cell">
                                        {{ $order->cod }}
                                    </td>

                                    <td class=" px-3 py-2 text-sm text-center text-slate-500 sm:table-cell">
                                        {{ $order->customer->name }} ({{ $order->customer->mobile }})
                                    </td>

                                    <td class=" px-3 py-2 text-sm text-right text-slate-500 sm:table-cell">
                                        {{ $reseller->business->name }} ( {{ $reseller->id_number }})
                                    </td>

                                </tr>
                            @endforeach

                        </tbody>
                        <tfoot>
                            <tr>
                                <th scope="row" colspan="4" class="text-left pt-2 text-sm font-bold">
                                    Total Parcel : {{ $orders->count() }} <br />
                                    Total Items : {{ $totalItems }} <br />
                                    Total COD : {{ $totalCod }} <br />
                                </th>

                            </tr>

                        </tfoot>
                    </table>
                </div>

                <div class="flex justify-between mt-12">
                    <div>
                        ----------------<br />
                        FreeSeller
                    </div>

                    <div>
                        ----------------<br />
                        Steadfast
                    </div>
                </div>

            </div>

        </div>


    </div>


    <script>
        window.print();
    </script>
@endsection
