@php
    use function App\Helpers\colorCode;
@endphp
@foreach ($getRecord()->colorQuantity() as $colorQnt)
    @php
        $parts = explode('-', $colorQnt);
        $colorName = explode(' ', $parts[0]);
    @endphp

    @if ($colorCode = colorCode(trim($colorName[0])))
        <span class="mx-1 text-xs font-medium px-2.5 py-0.5 rounded" style="background-color: {{ $colorCode }}">
            <span class="">{{ $parts[1] }}</span>

        </span>
    @endif
@endforeach
