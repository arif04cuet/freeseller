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

class ZipAndSendToReseller implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $reseller,
        //public string $tmpDir,
        public string $folderName,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //$zip = new \ZipArchive();

        // $tmpDir = $this->tmpDir;
        $folderName = $this->folderName;
        $zipFileName = $folderName . '.zip';
        // $destination = public_path($tmpDir . '/' . $zipFileName);

        $tmpDirPath = public_path('tmp');
        $command = "cd $tmpDirPath; zip -r $zipFileName $folderName";
        exec($command);

        $dirTobeDeleted = $tmpDirPath . '/' . $folderName;

        File::isDirectory($dirTobeDeleted) && File::deleteDirectory($dirTobeDeleted);

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
}
