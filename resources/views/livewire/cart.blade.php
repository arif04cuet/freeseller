@php
    use function App\Helpers\money;
@endphp
<div>
    <style>
        input[type='number'] {
            -moz-appearance: textfield;
        }

        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
        }
    </style>
    @if ($open)
        <form wire:submit.prevent="createOrder">
            <div class="relative z-10" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">

                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                <div class="fixed inset-0 overflow-hidden">
                    <div class="absolute inset-0 overflow-hidden">
                        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-0">
                            <div class="pointer-events-auto w-screen max-w-md">
                                <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">


                                    <div class="flex-1 overflow-y-auto px-4 py-6 sm:px-6">
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-center gap-4">
                                                <h2 class="text-lg font-medium text-gray-900" id="slide-over-title">
                                                    Shopping cart
                                                </h2>
                                                <x-filament::icon-button type="button" wire:click="clearCart()"
                                                    label="Clear cart" icon="heroicon-o-trash"
                                                    class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                                            </div>
                                            <div class="ml-3 flex h-7 items-center">
                                                <x-filament::icon-button type="button" wire:click="close"
                                                    label="Close" icon="heroicon-m-x-mark"
                                                    class="relative -m-2 p-2 text-gray-400 hover:text-gray-500" />
                                            </div>
                                        </div>

                                        <div class="mt-8">
                                            <div class="flow-root">
                                                @if ($content->count())
                                                    <ul role="list" class="-my-6 divide-y divide-gray-200">
                                                        @foreach ($content as $id => $item)
                                                            <li class="">

                                                                <x-cart-item image="{{ $item->options['image'] ?? '' }}"
                                                                    id="{{ $id }}" name="{{ $item->name }}"
                                                                    quantity="{{ $item->quantity }}"
                                                                    price="{{ money($item->price) }}" />

                                                            </li>
                                                        @endforeach
                                                        <!-- More products... -->
                                                    </ul>
                                                @else
                                                    <div class="flex item-center justify-center text-yellow-500">Ops
                                                        cart is empty</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    @if ($total)


                                        <div class="border-t border-gray-200 px-4 py-4 sm:px-6">
                                            <div
                                                class="flex justify-between text-base font-medium text-gray-900 pb-2 border-b border-gray-200">
                                                <p>Customer</p>

                                                <div>
                                                    @livewire('customer-search')
                                                    @error('customerId')
                                                        <span class="error">{{ $message }}</span>
                                                    @enderror
                                                </div>

                                            </div>

                                            <div class="flex justify-between text-base font-medium text-gray-900">
                                                <p>Subtotal</p>
                                                <p>{{ money($total) }}</p>
                                            </div>
                                            <div class="flex justify-between text-base font-medium text-gray-900">
                                                <p>Branding Charge</p>
                                                <p>{{ money($brandingCharge) }}</p>
                                            </div>
                                            <div class="flex justify-between text-base font-medium text-gray-900">
                                                <p>Delivery Charge</p>
                                                <p>{{ money($deliveryCharge) }}</p>
                                            </div>
                                            <hr />
                                            <div class="flex justify-between text-base font-medium text-gray-900">
                                                <p>Payable</p>
                                                <p>{{ money((int) $total + (int) $brandingCharge + (int) $deliveryCharge) }}
                                                </p>
                                            </div>

                                            <div class="flex justify-between text-base font-medium text-gray-900 mt-2">
                                                <p>COD</p>
                                                <div class="w-1/5">
                                                    <div class="relative">
                                                        <div
                                                            class="absolute inset-y-0 start-0 flex items-center ps-1.5 pointer-events-none">
                                                            ৳
                                                        </div>
                                                        <input wire:model.blur="cod" required type="number"
                                                            id="input-group-1"
                                                            class="text-right bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pr-0 pl-5 py-1  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                                    </div>
                                                </div>
                                            </div>
                                            @if ($cod)
                                                <div class="flex justify-between text-base font-medium text-gray-900">
                                                    <p>Profit</p>
                                                    <p>{{ money((int) $cod - ((int) $total + (int) $brandingCharge + (int) $deliveryCharge)) }}
                                                    </p>
                                                </div>
                                            @endif
                                            <div class="mt-2" x-data="{ show: false }">
                                                <span @click="show=true"
                                                    class="bg-indigo-600 p-1 text-white cursor-pointer rounded">Note?</span>
                                                <div x-show="show" class="flex-col gap-2 mt-1">
                                                    <textarea wire:model="note_for_wholesaler" class="w-full px-2 text-sm border" placeholder="Note for Wholesaler"></textarea>
                                                    <textarea wire:model="note_for_courier" class="w-full px-2 text-sm border" placeholder="Note for Courier"></textarea>
                                                </div>
                                            </div>

                                            <div class="mt-6">
                                                @if ($customerId && $this->canPlaceOrder)
                                                    <x-filament::button type="submit" wire:target="createOrder"
                                                        wire:loading.remove
                                                        class="flex w-full items-center justify-center rounded-md border border-transparent bg-indigo-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-indigo-700">
                                                        Place Order
                                                    </x-filament::button>
                                                    <div wire:target="createOrder" wire:loading class="flex gap-3">
                                                        <span>Please wait .. </span>
                                                        <x-filament::loading-indicator class="h-5 w-5" />
                                                    </div>
                                                @endif
                                            </div>


                                        </div>
                                    @endif


                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @endif
</div>
