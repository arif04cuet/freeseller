@extends('layout.print')

@section('content')
    <x-order.invoice :order="$order" />
    <script>
        window.print();
    </script>
@endsection
