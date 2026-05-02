<?php

use App\Classes\Settings;
use App\Console\Commands\CronJob;
use App\Console\Commands\FetchEmails;
use App\Console\Commands\ScheduleHeartbeatCommand;
use App\Console\Commands\TelemetryCommand;
use App\Jobs\Marketplace\SyncMarketplaceJob;
use Illuminate\Support\Facades\Schedule;

Schedule::command(ScheduleHeartbeatCommand::class)->description('Updates the last scheduler run time')->everyMinute()->onOneServer();
Schedule::command(CronJob::class)->description('Runs daily to send out invoices, suspend servers, etc.')->dailyAt(config('settings.cronjob_time', '00:00'))->onOneServer();
Schedule::command(FetchEmails::class)->description('Import ticket emails using IMAP')->everyFiveMinutes()->withoutOverlapping()->onOneServer();
Schedule::job(new SyncMarketplaceJob)->description('Sync the self-hosted marketplace catalog')->hourly()->onOneServer();

if (config('settings.backup_enabled', false)) {
    $cron = config('settings.backup_schedule', '0 3 * * *');
    Schedule::command('backup:run', ['--only-to-disk' => 'local'])->cron($cron)->onOneServer();
    Schedule::command('backup:clean')->daily()->onOneServer();
}

if (config('app.telemetry_enabled')) {
    $settings = Settings::getTelemetry();
    Schedule::command(TelemetryCommand::class)->description('Sends telemetry data')->dailyAt($settings['hour'] . ':' . $settings['minute']);
}
