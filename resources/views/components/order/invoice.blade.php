<div>

    @php
        $reseller = $order->reseller;
        $address = $reseller->address;
        $business = $reseller->business;
        $customer = $order->customer;
    @endphp

    <div class="mx-auto pt-5 max-w-3xl">
        <div class="mb-4 flex justify-between font-bold">
            <div>Parcel ID# {{ $order->consignment_id }}</div>
            <div class="">মার্চেন্ট ফোন নাম্বার # {{ $reseller->mobile }}</div>
            <div>Merchant ID# {{ config('services.steadfast.merchant_id') }}</div>

        </div>
        <div class="flex justify-between gap-3">

            <div class="flex gap-2 items-start w-3/6">
                @if ($logo = $business->logo)
                    <img class="w-12 " src="/storage/{{ $logo }}" alt="" srcset="">
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
            <div class="w-2/6">
                <h4 class="text-xl"> {{ $customer->name }}</h4>
                <p>{{ $customer->mobile }}</p>
                <p>{{ $customer->address }}</p>

            </div>
        </div>



        <div class="max-w-5xl mx-auto py-2 bg-white">

            <div class="bg-[white] rounded-b-md">

                <div class="flex flex-col mx-0 mt-4">
                    <table class="min-w-full divide-y divide-slate-500">
                        <thead>
                            <tr>
                                <th scope="col"
                                    class="py-3.5 pl-4 pr-3 text-left text-sm font-normal text-slate-700 sm:pl-6 md:pl-0">
                                    Products for order # {{ $order->id }}
                                </th>
                                <th scope="col"
                                    class=" py-3.5 px-3 text-right text-sm font-normal text-slate-700 sm:table-cell">
                                    Quantity
                                </th>
                            </tr>
                        </thead>
                        <tbody>

                            @foreach ($order->items as $item)
                                <tr class="border-b border-slate-200">
                                    <td class="py-2 pl-4 pr-3 text-sm sm:pl-6 md:pl-0">
                                        <div class="font-medium text-slate-700">{{ $item->sku->name }}</div>
                                    </td>
                                    <td class=" px-3 py-2 text-sm text-right text-slate-500 sm:table-cell">
                                        {{ $item->quantity }}
                                    </td>
                                </tr>
                            @endforeach

                        </tbody>
                        <tfoot>
                            <tr>
                                <th scope="row" colspan="3"
                                    class="pt-2 pl-6 pr-3 text-sm font-bold text-right text-slate-500 sm:table-cell md:pl-0">
                                    COD
                                </th>

                                <td class="pt-2 pl-3 pr-4 text-sm font-bold text-right text-slate-500 sm:pr-6 md:pr-0">
                                    {{ $order->cod }}
                                </td>
                            </tr>

                        </tfoot>
                    </table>
                </div>


            </div>

        </div>

    </div>


</div>
