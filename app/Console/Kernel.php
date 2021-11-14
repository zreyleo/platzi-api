<?php

namespace App\Console;

use App\Console\Commands\SendNewsletterCommand;
use App\Console\Commands\SendVerficationEmailCommand;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        SendNewsletterCommand::class,
        SendVerficationEmailCommand::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('inspire')
            ->evenInMaintenanceMode()
            ->sendOutputTo(storage_path('inspire.log'), true)
            ->everyMinute();

        $schedule->call(function () {
            echo "hola";
        })->everyFiveMinutes();

        $schedule->command(SendNewsletterCommand::class)
            ->withoutOverlapping()
            ->onOneServer()
            ->mondays();

        $schedule->command(SendVerficationEmailCommand::class)
            ->onOneServer()
            ->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
