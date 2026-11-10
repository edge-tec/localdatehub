<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Production Scheduled Jobs & Automation
|--------------------------------------------------------------------------
|
| Scheduled maintenance routines for LocalAdZone:
| 1. Expire outdated promotions and bumps hourly.
| 2. Process automated subscription renewals daily past midnight.
| 3. Send expiring listing notifications to posters at 08:00 AM daily.
| 4. Reconcile transactions with payment gateways daily at 03:00 AM.
|
*/

Schedule::command('promotions:expire')
    ->hourly()
    ->withoutOverlapping(10)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduler-promotions.log'));

Schedule::command('billing:process-renewals')
    ->dailyAt('00:30')
    ->withoutOverlapping(30)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduler-billing.log'));

Schedule::command('listings:notify-expiring')
    ->dailyAt('08:00')
    ->withoutOverlapping(20)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduler-notifications.log'));

Schedule::command('billing:reconcile-transactions')
    ->dailyAt('03:00')
    ->withoutOverlapping(30)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduler-reconciliation.log'));

Schedule::command('boosts:expire')
    ->everyFiveMinutes()
    ->withoutOverlapping(5)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduler-boosts.log'));

Schedule::command('crypto:check-pending')
    ->everyTenMinutes()
    ->withoutOverlapping(10)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduler-crypto.log'));

Schedule::command('crypto:monitor-inhouse')
    ->everyMinute()
    ->withoutOverlapping(1)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduler-inhouse-crypto.log'));

// Enterprise SEO Automation Schedules
Schedule::job(new \App\Modules\SEO\Jobs\ProcessIndexingQueueJob)
    ->everyFifteenMinutes()
    ->withoutOverlapping(10);

Schedule::job(new \App\Modules\SEO\Jobs\GenerateSitemapsJob)
    ->dailyAt('02:00')
    ->withoutOverlapping(30);

Schedule::job(new \App\Modules\SEO\Jobs\RunTechnicalSeoAuditJob)
    ->dailyAt('04:00')
    ->withoutOverlapping(30);

Schedule::job(new \App\Modules\SEO\Jobs\SyncGoogleSearchConsoleJob)
    ->dailyAt('06:00')
    ->withoutOverlapping(30);
