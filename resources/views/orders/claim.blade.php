<div>

    <div class="">
        <h3>Order Id: {{ $claim->order->id }}</h3>
        <h3>Claim amount: {{ $claim->order->courier_charge }} taka ( after accepting claim, amount will be deducted from
            your wallet to reseller wallet)</h3>
    </div>
    <br />
    <table class="table w-full">
        <tr>
            <th class="text-left w-1/3">Product</th>
            <th class="text-left w-2/3">Customer Images</th>
        </tr>

        @foreach ($items as $item)
            <tr>
                <td class="w-1/4">
                    <img src="{{ $item['sku']->getMedia('sharees')->first()?->getUrl('thumb') }}" alt=""
                        srcset=""><br />
                    {{ $item['sku']->name }}
                </td>
                <td class="">
                    <div class="flex">
                        @foreach ($claim->getMedia('claims-' . $item['item_id']) as $media)
                            <a href="{{ $media->getUrl() }}">
                                <img class="border-2" src="{{ $media->getUrl() }}" loading="lazy" width="200" />
                            </a>
                        @endforeach
                    </div>
                </td>
            </tr>
        @endforeach

    </table>
</div>
