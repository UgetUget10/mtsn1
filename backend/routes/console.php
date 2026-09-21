<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Jadwal ala wp-cron
|--------------------------------------------------------------------------
| Jalankan worker cron sistem sekali:  * * * * * php artisan schedule:run
*/
Schedule::command('posts:publish-due')->everyMinute()->withoutOverlapping();
Schedule::command('content:empty-trash')->dailyAt('03:10');
