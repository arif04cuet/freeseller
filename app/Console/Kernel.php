<?php

namespace App\Console;

use App\Jobs\LowStockNotification;
use App\Jobs\SavePathaoToken;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('cache:clear')->daily();

        $schedule->command('queue:work --stop-when-empty')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command('app:delete-backup')->daily();
        $schedule->command('app:delete-image-zip')->daily();

        $schedule->job(new LowStockNotification)->daily();
        //$schedule->job(new SavePathaoToken)->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
