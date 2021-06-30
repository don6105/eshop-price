<?php

namespace App\Console;

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
        \App\Console\Commands\Exchange::class,
        \App\Console\Commands\GameCrawl::class,
        \App\Console\Commands\SummarySync::class,
        \App\Console\Commands\SummaryGroup::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $log = storage_path().'/schedule.log';
        $schedule->command('game:crawl us --schedule')
            ->dailyAt('05:00')
            ->appendOutputTo($log);
        $schedule->command('game:crawl hk --schedule')
            ->dailyAt('05:00')
            ->appendOutputTo($log);

        $schedule->command('exchange:pull')
            ->dailyAt('05:00')
            ->appendOutputTo($log);

        $schedule->command('summary:sync us --schedule')
            ->everyFiveMinutes()
            ->withoutOverlapping(5);
        $schedule->command('summary:sync hk --schedule')
            ->everyFiveMinutes()
            ->withoutOverlapping(5);
        
        $schedule->command('summary:group --schedule')
            ->hourly()
            ->withoutOverlapping(30);
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
