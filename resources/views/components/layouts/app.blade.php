<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>{{ $title ?? config('app.name') }}</title>
</head>

<body>

    <header>
        <nav class="bg-white border-gray-200 dark:bg-gray-900">
            <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4 md:px-0">
                <a wire:navigate href="/" class="flex items-center">
                    <span class="self-center text-xl font-semibold whitespace-nowrap dark:text-white">
                        ফ্রিসেলার
                        <span class="text-sm"> 1.0 </span>
                    </span>
                </a>
                <div>
                    @livewire('cart-button')
                </div>
                <button data-collapse-toggle="navbar-default" type="button"
                    class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600"
                    aria-controls="navbar-default" aria-expanded="false">
                    <span class="sr-only">Open main menu</span>
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 17 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M1 1h15M1 7h15M1 13h15" />
                    </svg>
                </button>
                <div class="hidden w-full md:block md:w-auto" id="navbar-default">

                    <ul
                        class="font-medium flex flex-col p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50 md:flex-row md:space-x-8 rtl:space-x-reverse md:mt-0 md:border-0 md:bg-white dark:bg-gray-800 md:dark:bg-gray-900 dark:border-gray-700">
                        <li>
                            <a wire:navigate href="{{ route('catalog') }}"
                                class="block py-2 px-3 hover:bg-blue-700 rounded md:bg-transparent hover:text-white p-2 dark:text-white md:dark:text-blue-500"
                                aria-current="page"> সকল প্রোডাক্ট</a>

                        </li>
                        @auth
                            <li>
                                <a href="{{ route('filament.app.auth.login') }}"
                                    class="block py-2 px-3 hover:bg-blue-700  rounded md:bg-transparent hover:text-white p-2 dark:text-white md:dark:text-blue-500"
                                    aria-current="page"> ড্যাশবোর্ড</a>

                            </li>
                        @else
                            <li>
                                <a href="{{ route('filament.app.auth.login') }}"
                                    class="block py-2 px-3 hover:bg-blue-700  rounded md:bg-transparent hover:text-white p-2 dark:text-white md:dark:text-blue-500"
                                    aria-current="page">লগইন
                                </a>

                            </li>
                            <li>
                                <a href="{{ route('filament.app.auth.register') }}"
                                    class="block py-2 px-3 hover:bg-blue-700  rounded md:bg-transparent hover:text-white p-2 dark:text-white md:dark:text-blue-500"
                                    aria-current="page"> রেজিস্ট্রেশান করুন</a>

                            </li>
                        @endauth


                    </ul>
                </div>
            </div>
        </nav>
    </header>


    <div class="mx-auto max-w-screen-xl">

        <x-success-message />

        <div>{{ $slot }}</div>
    </div>
    <div>
        @livewire('cart')
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.8.1/flowbite.min.js"></script>
    <script>
        document.addEventListener('livewire:navigated', () => {
            initFlowbite();
        })
    </script>
</body>

</html>
