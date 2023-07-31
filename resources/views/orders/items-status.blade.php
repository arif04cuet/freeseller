<table class="w-full leading-normal">
    <thead>
        <tr>
            <th
                class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                Product
            </th>
            <th
                class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                Item Name
            </th>
            <th
                class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                Quantity
            </th>
            <th
                class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                Status
            </th>
            <th
                class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                Wholesaler
            </th>

        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
            <tr>
                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                    <div class="flex">
                        {{-- <div class="flex-shrink-0 w-10 h-10">
                            <img class="shadow responsive" style="max-width: 100px"
                                src="{{ $item->sku->getMedia('sharees')->first()->getUrl('thumb') }}" alt=""
                                srcset="" />
                        </div> --}}
                        <div class="ml-3">
                            <p class="text-gray-900 whitespace-no-wrap">
                                {{ $item->sku->product->name }}
                            </p>

                        </div>
                    </div>
                </td>
                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                    <p class="text-gray-900 whitespace-no-wrap">{{ $item->sku->name }}</p>

                </td>
                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                    <p class="text-gray-900 whitespace-no-wrap">{{ $item->quantity }}</p>
                </td>
                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                    <span class="relative inline-block px-3 py-1 font-semibold text-green-900 leading-tight">
                        <span aria-hidden class="absolute inset-0 bg-green-200 opacity-50 rounded-full"></span>
                        <span class="relative">{{ $item->status->value }}</span>
                    </span>
                </td>
                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                    <span class="relative inline-block px-3 py-1 font-semibold text-green-900 leading-tight">
                        <span aria-hidden class="absolute inset-0 bg-green-200 opacity-50 rounded-full"></span>
                        <span class="relative">{{ $item->wholesaler->name }}</span>
                    </span>
                </td>
            </tr>
        @endforeach

    </tbody>
</table>
