<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeleteBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-backup';

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
        $comand = 'cd /home/sites/26a/9/971c75b864/Backup/DB;find . -type f -mtime +2 -exec rm -rf {} \;';
        exec($comand);
    }
}
