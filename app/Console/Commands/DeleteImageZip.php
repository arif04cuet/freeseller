<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeleteImageZip extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-image-zip';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $comand = 'cd /home/sites/26a/9/971c75b864/public_html/github/freeseller/public/tmp;find . -type f -name \*.zip -exec rm -rf {} \;';
        exec($comand);
    }
}
