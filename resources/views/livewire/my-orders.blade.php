<div class="relative overflow-x-auto ">

    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-6 py-3">
                    Order#
                </th>
                <th scope="col" class="px-6 py-3">
                    Customer
                </th>
                <th scope="col" class="px-6 py-3">
                    Status
                </th>
                <th scope="col" class="px-6 py-3">
                    Items
                </th>
                <th scope="col" class="px-6 py-3">
                    Payable
                </th>
                <th scope="col" class="px-6 py-3">
                    Cod/C.Cod/Profit
                </th>
                <th scope="col" class="px-6 py-3">
                    CN
                </th>
                <th scope="col" class="px-6 py-3">
                    Created at
                </th>
                <th scope="col" class="px-6 py-3">

                </th>


            </tr>
        </thead>
        <tbody>
            @foreach ($this->orders as $order)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ $order->id }}
                    </th>
                    <td class="px-6 py-4">
                        {{-- {{ $order->customer }} --}}
                        {{-- {{ '<a href="tel:' . $order->customer->mobile . '"><u>' . $order->customer->name . '<br/>' . $order->customer->mobile . '</u></a>' }} --}}
                    </td>
                    <td class="px-6 py-4">
                        {{ $order->status->getLabel() }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $order->items_count }}
                    </td>
                    <td class="px-6 py-4">
                        ৳ {{ $order->total_payable }}
                    </td>
                    <td class="px-6 py-4">
                        ৳{{ $order->cod }} <br>
                        ৳{{ $order->collected_cod ?? 'n/a' }} <br>
                        ৳{{ $order->profit }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $order->consignment_id }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $order->created_at->format('d/m/Y') }}
                    </td>

                    <td class="px-6 py-4">
                        @if ($order->delivered_at)
                            <x-filament::icon-button icon="heroicon-m-eye"
                                wire:click="transactions({{ $order->id }})" label="Transactions" />
                        @endif
                        @if (in_array($order->status, [
                                \App\Enum\OrderStatus::WaitingForWholesalerApproval,
                                \App\Enum\OrderStatus::WaitingForHubCollection,
                            ]))
                            <x-filament::icon-button icon="heroicon-m-pencil" wire:click="edit({{ $order->id }})"
                                label="Edit" />
                        @endif

                    </td>

                </tr>
            @endforeach

        </tbody>
    </table>


    <div class="mt-4"> {{ $this->orders->links() }}</div>

    <x-filament::modal id="transaction_modal" width="lg">
        @if ($transactionOrder)
            @livewire(
                'order-transactions',
                [
                    'order' => $transactionOrder,
                ],
                key($transactionOrder->id)
            )
        @endif
    </x-filament::modal>


</div>
