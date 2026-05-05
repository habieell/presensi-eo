<?php

use App\Console\Commands\PurgeExpiredCaptchas;
use App\Console\Commands\SendEventReminders;
use App\Console\Commands\RetryFailedNotifications;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment('💡 Stay disciplined!');
})->purpose('Display an inspiring quote');

// ── Scheduled ──
Schedule::command(SendEventReminders::class)->everyFiveMinutes()->withoutOverlapping();
Schedule::command(PurgeExpiredCaptchas::class)->hourly();
Schedule::command(RetryFailedNotifications::class)->everyFifteenMinutes();
Schedule::command('queue:retry all')->daily();
