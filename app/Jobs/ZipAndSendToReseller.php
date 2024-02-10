<?php

namespace App\Jobs;

use App\Models\User;
use Filament\Notifications\Actions\Action;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use ZipArchive;

class ZipAndSendToReseller implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $reseller,
        public array $files,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $zipFileName = uniqid() . '.zip';

        $this->makeZipWithFiles($zipFileName, $this->files);

        User::sendMessage(
            users: $this->reseller,
            title: 'Download product images',
            actions: [
                Action::make('Download')
                    ->button()
                    ->markAsRead()
                    ->url('/tmp/' . $zipFileName)
            ]
        );
    }

    public function makeZipWithFiles(string $zipPathAndName, array $filesAndPaths): void
    {
        $zip = new ZipArchive();

        $dirname = public_path('tmp/' . $zipPathAndName);

        if ($zip->open($dirname, ZipArchive::CREATE) !== TRUE) {
            logger('Could not open ZIP file.');
            return;
        }

        // Add File in ZipArchive
        foreach ($filesAndPaths as $file) {
            if (!$zip->addFile($file, basename($file))) {
                logger('Could not add file to ZIP: ' . $file);
            }
        }
        // Close ZipArchive
        $zip->close();
    }
}
