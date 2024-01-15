<div>

    @php
        $reseller = $order->reseller;
        $address = $reseller->address;
        $business = $reseller->business;
        $customer = $order->customer;
    @endphp

    <div class="mx-auto max-w-3xl break-inside-avoid">
        <div class="mb-2 flex justify-between font-bold px-2">
            <div>Parcel ID# {{ $order->consignment_id }}</div>
            <div>Merchant ID# {{ config('services.steadfast.merchant_id') }}</div>
            <div>Order ID# {{ $order->id }}</div>
        </div>
        <div class="text-center font-bold mb-2">মার্চেন্ট ফোন নাম্বার # {{ $reseller->mobile }}</div>
        <div class="flex justify-between gap-2">

            <div class="w-1/2 text-wrap">
                <div class="">
                    @if ($logo = $business->logo)
                        <img class="w-12 float-left mr-2 " src="/storage/{{ $logo }}" alt="" srcset="">
                    @endif
                    <div class="text-left">
                        <h4 class="text-xl"> {{ $business->name }}</h4>
                        @if ($slogan = $business->slogan)
                            <p class="text-sm italic mb-2">
                                {{ $slogan }}
                            </p>
                        @endif
                        <p class="text-wrap">{{ $address->address }}</p>
                    </div>
                </div>
            </div>
            <div class="w-1/2 text-right">
                <h4 class="text-xl"> {{ $customer->name }}</h4>
                <p>{{ $customer->mobile }}</p>
                <p>{{ $customer->address }}</p>

            </div>
        </div>



        <div class="max-w-5xl mx-auto py-2 bg-white">

            <div class="bg-[white] rounded-b-md">

                <div class="flex flex-col mx-0">
                    <table class="min-w-full divide-y divide-slate-500">
                        <thead>
                            <tr>
                                <th scope="col"
                                    class="py-1 pl-4 pr-3 text-left text-sm font-normal text-slate-700 sm:pl-6 md:pl-0">
                                    Product
                                </th>
                                <th scope="col"
                                    class=" py-1 px-3 text-right text-sm font-normal text-slate-700 sm:table-cell">
                                    Quantity
                                </th>
                            </tr>
                        </thead>
                        <tbody>

                            @foreach ($order->items as $item)
                                <tr class="border-b border-slate-200">
                                    <td class="py-2 pl-4 pr-3 text-sm sm:pl-6 md:pl-0">
                                        <div class="font-medium text-slate-700">
                                            {{ $item->sku->loadMissing(['product.category'])->product->category->name }}
                                        </div>
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
