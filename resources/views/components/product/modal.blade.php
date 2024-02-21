<x-filament::modal id="modal-{{ $product->id }}">
    <x-slot name="heading">
        {{ $product->name }}
    </x-slot>
    <x-slot name="description">
        Modal description
    </x-slot>
</x-filament::modal>
