<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('exports:cleanup')->dailyAt('02:00');

// Elasticsearch indices cleanup based on retention policy
// Runs daily at 2:30 AM to clean up old patient-dashboard-metrics indices
// Configure retention settings in config/elasticsearch.php under 'retention'
if (config('elasticsearch.retention.schedule.enabled', true)) {
    Schedule::command('wellmed-backbone:flush-elasticsearch-indices --force')
        ->dailyAt('02:30')
        ->withoutOverlapping()
        ->onOneServer()
        ->appendOutputTo(storage_path('logs/elasticsearch-flush.log'));
}