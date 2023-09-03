<div class="flex flex-col md:flex-row gap-4">

    @foreach ($record->getAllImages() as $media)
        <div>
            <a href="{{ $media->getUrl() }}">
                <img class="h-auto max-w-full rounded-lg" src="{{ $media->getUrl() }}" alt="" srcset="" />
            </a>
        </div>
    @endforeach


</div>
