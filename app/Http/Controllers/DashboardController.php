<?php

namespace App\Http\Controllers;

use App\Domain\Assets\Enums\AssetStatus;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetCategory;
use App\Domain\Assets\Models\AssetMovement;
use App\Domain\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController
{
    public function __invoke(CurrentTenant $currentTenant): View
    {
        $tenant = $currentTenant->require();
        $metrics = Cache::remember("tenant:{$tenant->id}:dashboard:summary", now()->addMinutes(15), fn () => [
            'total_assets' => Asset::query()->forTenant($tenant->id)->count(),
            'in_use' => Asset::query()->forTenant($tenant->id)->where('status', AssetStatus::Active)->count(),
            'maintenance' => Asset::query()->forTenant($tenant->id)->where('status', AssetStatus::UnderMaintenance)->count(),
            'pending_movements' => AssetMovement::query()->forTenant($tenant->id)->where('status', 'pending_approval')->count(),
            'active_loans' => AssetMovement::query()->forTenant($tenant->id)->where('movement_type', 'loan')->whereNull('returned_at')->count(),
            'upcoming_returns' => AssetMovement::query()->forTenant($tenant->id)->whereNull('returned_at')->whereNotNull('expected_return_at')->whereBetween('expected_return_at', [now()->startOfDay(), now()->addDays((int) config('taggo.asset_return_reminder_days', 3))->endOfDay()])->count(),
            'overdue_returns' => AssetMovement::query()->forTenant($tenant->id)->whereNull('returned_at')->whereNotNull('expected_return_at')->where('expected_return_at', '<', now())->count(),
            'recent_transfers' => AssetMovement::query()->forTenant($tenant->id)->where('movement_type', 'internal_transfer')->where('created_at', '>=', now()->subDays(15))->count(),
            'without_custodian' => Asset::query()->forTenant($tenant->id)->whereNull('custodian_id')->count(),
            'catalog_items' => AssetCategory::query()->forTenant($tenant->id)->count(),
        ]);

        return view('dashboard', compact('metrics'));
    }
}
