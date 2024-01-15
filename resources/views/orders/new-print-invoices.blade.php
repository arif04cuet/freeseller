@extends('layout.print')
@section('content')
    <div class="main-page">
        <div class="sub-page px-2 py-4">

            <div class="grid grid-cols-2 gap-4">

                @foreach ($orders as $order)
                    <x-order.new-invoice :order="$order" />
                @endforeach

            </div>

        </div>
    </div>
    <script>
        //window.print();
    </script>
@endsection
