<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Phase 16 — no real cron runs in this environment, but this is how a
// real deployment would wire it up: daily reminders for subscriptions
// ending soon and trials ending soon.
Schedule::command('billing:send-reminders')->daily();
