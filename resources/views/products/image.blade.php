<div class="flex">
    @foreach ($record->getMedia('sharees') as $media)
        <img class="rounded shadow responsive" style="max-width: 100px" src="{{ $media->getUrl('thumb') }}" alt=""
            srcset="" />
    @endforeach
</div>
