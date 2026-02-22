<?php

use App\Services\EventNotificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notifications:send-event-reminders {--hours=24}', function (EventNotificationService $service) {
    $hours = (int) $this->option('hours');
    $sent = $service->sendStartingSoonNotifications($hours);

    $this->info("Event starting soon notifications sent: {$sent}");
})->purpose('Send reminders to students for registered events starting soon');

Schedule::call(function (EventNotificationService $service): void {
    $service->sendStartingSoonNotifications(24);
})->everyMinute();
