<table class="min-w-full divide-y divide-slate-500">
    <thead>
        <tr class="px-2">
            <th scope="col" class="py-3.5 p-4 pr-3 text-left text-sm text-slate-700 sm:pl-6 md:pl-0">
                Image
            </th>
            <th scope="col" class="py-3.5 p-4 pr-3 text-left text-sm text-slate-700 sm:pl-6 md:pl-0">
                Item
            </th>
            <th scope="col" class=" py-3.5 px-3 text-right text-sm text-slate-700 sm:table-cell">
                Quantity
            </th>
        </tr>
    </thead>
    <tbody>

        @foreach ($items as $item)
            @php
                $sku = App\Models\Sku::find($item['sku']);
            @endphp
            <tr class="border-b border-slate-200">
                <td class="py-4 pl-4 pr-3 text-sm sm:pl-6 md:pl-0">
                    <img src="{{ $sku->getMedia('*')->first()->getUrl('thumb') }}" alt="" srcset="">
                </td>
                <td class="py-4 pl-4 pr-3 text-sm sm:pl-6 md:pl-0">
                    <div class="font-medium text-slate-700">{{ $sku->name }}</div>
                </td>
                <td class=" px-3 py-4 text-sm text-right text-slate-500 sm:table-cell">
                    {{ $item['quantity'] }}
                </td>
            </tr>
        @endforeach

    </tbody>
    <tfoot>
        <tr>
            <th scope="row" class="pt-6 pl-6 pr-3 text-sm font-bold text-right text-slate-500 sm:table-cell md:pl-0">
                COD
            </th>

            <td class="pt-6 pl-3 pr-4 text-sm font-bold text-right text-slate-500 sm:pr-6 md:pr-0">
                {{ $cod }}
            </td>
        </tr>

    </tfoot>
</table>
