<?php

namespace App\Http\Controllers;

use App\Domain\Assets\Enums\AssetStatus;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetCategory;
use App\Domain\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController
{
    public function __invoke(CurrentTenant $currentTenant): View
    {
        $tenant = $currentTenant->require();
        $metrics = Cache::remember("tenant:{$tenant->id}:dashboard:summary", now()->addMinutes(15), fn () => ['total_assets' => Asset::query()->forTenant($tenant->id)->count(), 'in_use' => Asset::query()->forTenant($tenant->id)->where('status', AssetStatus::Active)->count(), 'maintenance' => Asset::query()->forTenant($tenant->id)->where('status', AssetStatus::UnderMaintenance)->count(), 'pending' => AssetCategory::query()->forTenant($tenant->id)->count()]);

        return view('dashboard', compact('metrics'));
    }
}
