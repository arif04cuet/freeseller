@php
    use function App\Helpers\money;
@endphp
<div>
    @if ($open)
        <div class="relative z-10" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
            <!--
          Background backdrop, show/hide based on slide-over state.

          Entering: "ease-in-out duration-500"
            From: "opacity-0"
            To: "opacity-100"
          Leaving: "ease-in-out duration-500"
            From: "opacity-100"
            To: "opacity-0"
        -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                        <!--
                Slide-over panel, show/hide based on slide-over state.

                Entering: "transform transition ease-in-out duration-500 sm:duration-700"
                  From: "translate-x-full"
                  To: "translate-x-0"
                Leaving: "transform transition ease-in-out duration-500 sm:duration-700"
                  From: "translate-x-0"
                  To: "translate-x-full"
              -->
                        <div class="pointer-events-auto w-screen max-w-md">
                            <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
                                <div class="flex-1 overflow-y-auto px-4 py-6 sm:px-6">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-center gap-4">
                                            <h2 class="text-lg font-medium text-gray-900" id="slide-over-title">
                                                Shopping cart
                                            </h2>
                                            <x-filament::icon-button wire:click="clearCart()" label="Clear cart"
                                                icon="heroicon-o-trash"
                                                class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                                        </div>
                                        <div class="ml-3 flex h-7 items-center">
                                            <button type="button" wire:click="close"
                                                class="relative -m-2 p-2 text-gray-400 hover:text-gray-500">
                                                <span class="absolute -inset-0.5"></span>
                                                <span class="sr-only">Close panel</span>
                                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-8">
                                        <div class="flow-root">
                                            @if ($content->count())
                                                <ul role="list" class="-my-6 divide-y divide-gray-200">
                                                    @foreach ($content as $id => $item)
                                                        <li class="flex py-6">
                                                            <div
                                                                class="h-24 w-24 flex-shrink-0 overflow-hidden rounded-md border border-gray-200">

                                                                <img src="{{ $item->options['image'] ?? '' }}"
                                                                    alt="{{ $item->name }}"
                                                                    class="h-full w-full object-cover object-center">
                                                            </div>

                                                            <div class="ml-4 flex flex-1 flex-col">
                                                                <div>
                                                                    <div
                                                                        class="flex justify-between text-base font-medium text-gray-900">
                                                                        <h3>
                                                                            <a href="#">{{ $item->name }}</a>
                                                                        </h3>
                                                                        <p class="ml-4">{{ money($item->price) }}</p>
                                                                    </div>
                                                                    <p class="mt-1 text-sm text-gray-500"></p>
                                                                </div>
                                                                <div
                                                                    class="flex flex-1 items-end justify-between text-sm">
                                                                    <p class="text-gray-500">
                                                                        Qty

                                                                        <button
                                                                            wire:click="updateCartItem({{ $id }},'minus')"
                                                                            class="px-2 rounded hover:bg-gray-400 bg-gray-500 text-white">-</button>
                                                                        {{ $item->quantity }}
                                                                        <button
                                                                            wire:click="updateCartItem({{ $id }},'plus')"
                                                                            class="px-2 rounded hover:bg-gray-400 bg-gray-500 text-white">+</button>
                                                                    </p>

                                                                    <div class="flex">
                                                                        <button
                                                                            wire:click="removeFromCart({{ $id }})"
                                                                            type="button"
                                                                            class="font-medium text-indigo-600 hover:text-indigo-500">Remove</button>
                                                                    </div>
                                                                </div>
                                                            </div>
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

                                <div class="border-t border-gray-200 px-4 py-6 sm:px-6">
                                    <div class="flex justify-between text-base font-medium text-gray-900">
                                        <p>Subtotal</p>
                                        <p>{{ $total }}</p>
                                    </div>
                                    <p class="mt-0.5 text-sm text-gray-500">Shipping and taxes calculated at checkout.
                                    </p>
                                    <div class="mt-6">
                                        <a href="#"
                                            class="flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-indigo-700">Checkout</a>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
