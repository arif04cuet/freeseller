<div class="flex">
    @foreach ($record->getMedia('sharees') as $media)
        <a href="{{ $media->getUrl() }}">
            <img class="rounded shadow responsive" style="max-width: 100px" src="{{ $media->getUrl('thumb') }}"
                alt="" srcset="" />
        </a>
    @endforeach
</div>
