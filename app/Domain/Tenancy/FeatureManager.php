<?php

namespace App\Domain\Tenancy;

use App\Domain\Tenancy\Models\TenantFeature;
use Illuminate\Support\Facades\Cache;

final class FeatureManager
{
    public function enabled(string $feature): bool
    {
        $tenantId = app(CurrentTenant::class)->id();

        return Cache::remember("tenant:{$tenantId}:feature:{$feature}", now()->addHour(), fn () => (bool) TenantFeature::query()->where('tenant_id', $tenantId)->where('feature', $feature)->value('enabled'));
    }

    public function forget(int $tenantId, string $feature): void
    {
        Cache::forget("tenant:{$tenantId}:feature:{$feature}");
    }
}
