<?php

namespace App\Listeners;

use App\Events\SkuCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Intervention\Image\ImageManagerStatic as Image;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAdded;

class AddSkuNumnerToImage
{

    public function handle(MediaHasBeenAdded $event)
    {
        $media = $event->media;
        if ($media->model_type == 'App\Models\Sku') {
            $this->addTextToImage($media);
        }
    }

    public function addTextToImage($media): void
    {
        $imagePath = $media->getPath();
        $sku = $media->model;

        $img = Image::make($imagePath);
        $img->text($sku->waterMarkText(), 6, 10);
        $img->save($imagePath);
    }
}
