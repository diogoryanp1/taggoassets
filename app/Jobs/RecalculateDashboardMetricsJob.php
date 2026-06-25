<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class RecalculateDashboardMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [10, 60, 300];

    public function __construct(public readonly int $tenantId) {}

    public function handle(): void
    {
        Cache::put("tenant:{$this->tenantId}:dashboard:summary", ['total_assets' => 0, 'in_use' => 0, 'maintenance' => 0, 'pending' => 0], now()->addMinutes(15));
    }
}
