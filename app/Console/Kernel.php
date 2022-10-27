<?php

namespace App\Console;

use App\Core\Events\EventAssistant;
use App\Core\Events\YclientsRequest;
use App\Core\Payment;
use App\Yclients\Cache\AdminConfig;
use App\Yclients\Cache\CompaniesInfo;
use App\Yclients\Connect\RequestArchitecture;
use App\Yclients\Modules\History\History;
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
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {\App\Core\Cron::cronCheckPayment();})
//            ->everyFiveMinutes();
        ->hourly();

        $schedule->call(function () {Payment::cronCheckPaymentSecond();})
            ->everyMinute();
//            ->hourly();

        $schedule->call(function () {CompaniesInfo::save();})->dailyAt('03:00');
        $schedule->call(function () {AdminConfig::save();})->dailyAt('03:00');
//        $schedule->call(function () {RequestArchitecture::execute();})->everyFiveMinutes();




        $schedule->call(function () {History::cronSendUnrecognizedMessageToAdmin();})->everyMinute();
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
