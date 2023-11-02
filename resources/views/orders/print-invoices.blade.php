@extends('layout.print')
@section('content')
    @foreach ($orders as $order)
        <x-order.invoice :order="$order" />
    @endforeach
    <script>
        window.print();
    </script>
@endsection
