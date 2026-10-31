<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Schedule::exec(escapeshellarg(base_path('bin/sync-market')), [PHP_BINARY])
    ->name('market-sync')
    ->everyTenSeconds()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduler.log'))
    ->onFailure(function (Event $event): void {
        Log::error('market_sync_process_failed', [
            'source' => config('market.source'), 'status' => 'failed',
            'exit_code' => $event->exitCode,
            'reason' => in_array($event->exitCode, [124, 137], true) ? 'process_deadline_exceeded' : 'command_failed',
            'deadline_seconds' => 40,
        ]);
    });
