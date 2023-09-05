<div class="filament-tables-table-container relative overflow-x-auto dark:border-gray-700 border-t">


    <table class="filament-tables-table w-full table-auto divide-y text-start dark:divide-gray-700">

        <thead>
            <tr class="bg-gray-500/5">

                <th class="filament-tables-header-cell p-0 py-2 px-4">
                    Product
                </th>
                <th class="filament-tables-header-cell p-0 py-2 px-4">
                    Item Name
                </th>
                <th class="filament-tables-header-cell p-0 py-2 px-4">
                    Quantity
                </th>
                <th class="filament-tables-header-cell p-0 py-2 px-4">
                    Status
                </th>
                <th class="filament-tables-header-cell p-0 py-2 px-4">
                    Wholesaler
                </th>

            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr class="filament-tables-row transition hover:bg-gray-50 dark:hover:bg-gray-500/10">
                    @include('layout.table-td', ['text' => $item->sku->product->name])
                    @include('layout.table-td', ['text' => $item->sku->name])

                    @include('layout.table-td', ['text' => $item->quantity])
                    @include('layout.table-td', ['text' => $item->status->value])

                    @php
                        $businessName = $item->wholesaler->business->first()->name;
                        $wholesaler = $item->wholesaler;
                    @endphp
                    @include('layout.table-td', [
                        'text' => $businessName . '(' . $wholesaler->name . '-' . $wholesaler->id . ')',
                    ])
                </tr>
            @endforeach

        </tbody>
    </table>

</div>
