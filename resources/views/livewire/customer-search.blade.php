@php
    $customer = $this->customer;

@endphp
<div class="pl-2">
    @if ($customer)
        <div class="flex gap-2">
            <span
                @class([
                    'text-red-500' => $customer->isFraud(),
                ])>{{ $customer->name . ' (' . $customer->mobile . '), ' . $customer->address }}
            </span>
            @if ($customer->isFraud())
                <span><x-filament::icon-button wire:click="fraudList" size="xs" color="danger"
                        icon="heroicon-c-face-smile" label="Fraud" /></span>
            @endif

            <span><x-filament::icon-button wire:click="newCustomerModal" size="xs" color="primary"
                    icon="heroicon-m-pencil" label="Edit" /></span>
            <span><x-filament::icon-button wire:click="removeCustomer" size="xs" color="danger"
                    icon="heroicon-m-trash" label="Delete" /></span>
        </div>
    @else
        <div class="flex items-center">
            <x-filament::loading-indicator class="h-5 w-5" wire:loading wire:loading.target="mobile" />
            <input type="text" placeholder="Search by Mobile" class="border w-44 px-2" wire:model.live="mobile"
                required>
        </div>
    @endif


    <div class="z-10 bg-white divide-y divide-gray-100 rounded-lg shadow w-44 dark:bg-gray-700">
        @if ($customers->count())
            <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownDefaultButton">

                @foreach ($customers as $customer)
                    <li wire:key="{{ $customer->id }}">
                        <a wire:click="selectedCustomer({{ $customer->id }})" href="#"
                            class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
                            {{ $customer->mobile . ' - ' . $customer->name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @else
            @if ($mobile)
                <button type="button" class="text-black p-2" wire:click="newCustomerModal">+ add new</button>
            @endif
        @endif
    </div>



    <div x-on:close-modal.window="if($event.detail.id == 'add-customer') $wire.open = false;">
        <x-filament::modal id="add-customer" slide-over>

            <x-slot name="heading">
                Modal heading
            </x-slot>

            <x-slot name="description">
                @if ($open)
                    <form wire:submit="create">



                        <div class="flex gap-4">

                            <div class="relative z-0 mb-5 group w-1/2">
                                <input wire:model="newCustomer.name" type="text" name="name" id="name"
                                    class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-blue-500 focus:outline-none focus:ring-0 focus:border-blue-600 peer"
                                    placeholder=" " required />
                                <label for="name"
                                    class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">
                                    Name
                                </label>
                            </div>

                            <div class="relative z-0 mb-5 group w-1/2">
                                <input pattern=".{11,11}" wire:model.blur="newCustomer.mobile" type="number"
                                    name="floating_mobile" id="floating_mobile"
                                    class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-blue-500 focus:outline-none focus:ring-0 focus:border-blue-600 peer"
                                    placeholder=" " required minlength="11" maxlength="11" />
                                <label for="floating_mobile"
                                    class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">
                                    Mobile
                                </label>
                            </div>

                        </div>

                        <div class="flex gap-4 mt-2">
                            <div class="relative z-0 mb-5 group w-full">
                                <input wire:model="newCustomer.address" type="text" name="floating_address"
                                    id="floating_address"
                                    class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-blue-500 focus:outline-none focus:ring-0 focus:border-blue-600 peer"
                                    placeholder=" " required />
                                <label for="floating_address"
                                    class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">
                                    Address
                                </label>
                            </div>
                        </div>

                        <div class="flex gap-4 mt-2">
                            <div class="w-1/2">
                                <label for="district"
                                    class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">District</label>
                                <select required id="district" wire:model.live="newCustomer.district_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">

                                    <option value="">Select</option>
                                    @foreach ($this->districts as $district)
                                        <option wire:key="{{ $district->id }}" value="{{ $district->id }}">
                                            {{ $district->name }}</option>
                                    @endforeach

                                </select>
                            </div>
                            <div class="w-1/2">
                                <label for="upazila"
                                    class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Upazila</label>
                                <select required id="upazila" wire:model="newCustomer.upazila_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">

                                    <option value="">Select</option>
                                    @foreach ($this->upazilas as $upazila)
                                        <option wire:key="{{ $upazila->id }}" value="{{ $upazila->id }}">
                                            {{ $upazila->name }}</option>
                                    @endforeach


                                </select>
                            </div>

                        </div>

                        <button type="submit"
                            class="mt-6 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Submit</button>
                    </form>
                @endif
            </x-slot>


        </x-filament::modal>
    </div>

    @if ($customer && $customer->isFraud())

        <div x-on:close-modal.window="if($event.detail.id == 'fraud-list') $wire.open = false;">
            <x-filament::modal id="fraud-list" slide-over>

                <x-slot name="heading">
                    {{ $customer->name }} ({{ $customer->mobile }})
                </x-slot>

                <x-slot name="description">
                    @if ($open)

                        <ul>
                            @foreach ($this->fraudMessages as $item)
                                <ol class="mb-2">{{ $item->pivot->message }}</ol>
                            @endforeach
                        </ul>
                    @endif
                </x-slot>


            </x-filament::modal>
        </div>
    @endif
</div>
