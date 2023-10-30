<?php

namespace App\Jobs;

use App\Models\Sku;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AddWaterMarkToMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Media $media,
        public Sku $sku,
        public string $position,
        public string $folder
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $media = $this->media;
        $sku = $this->sku;
        $folder = $this->folder;
        $path = $media->getPath();

        if (!File::exists($path)) {
            return;
        }

        $img = Image::make($path);
        [$x, $y] = $this->imageXY($img, $this->position);
        $img->text($sku->waterMarkText(), $x, $y);

        $savePath = $folder . '/' . uniqid("$sku->id-") . '.png';
        $img->save($savePath);
    }

    public function imageXY($img, $watermark_position): array
    {
        $pad = 10;
        switch ($watermark_position) {
            case 'top_left':
                $x = $y = $pad;
                break;
            case 'top_right':
                $x = $img->width() - ($pad + 10);
                $y = $pad;
                break;

            case 'bottom_left':
                $x = $pad;
                $y = $img->height() - $pad;
                break;

            case 'bottom_right':
                $x = $img->width() - ($pad + 10);
                $y = $img->height() - $pad;
                break;
            default:
                $x = $y = $pad;
                break;
        }

        return [$x, $y];
    }
}
