<div class="flex flex-col md:flex-row gap-4">

    @foreach ($record->getAllImages() as $media)
        <div>
            <img class="h-auto max-w-full rounded-lg" src="{{ $media->getUrl() }}" alt="" srcset="">
        </div>
    @endforeach


</div>
