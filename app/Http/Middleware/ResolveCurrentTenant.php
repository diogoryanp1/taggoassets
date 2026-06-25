<?php

namespace App\Http\Middleware;

use App\Domain\Tenancy\CurrentTenant;
use App\Domain\Tenancy\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCurrentTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenantPublicId = $request->session()->get('active_tenant');
        if (! $user || $user->status !== 'active' || ! $tenantPublicId) {
            abort(403, 'Selecione uma organização para continuar.');
        }
        $tenant = Tenant::query()->where('public_id', $tenantPublicId)->where('status', 'active')->first();
        if (! $tenant || ! $user->tenants()->whereKey($tenant->id)->wherePivot('status', 'active')->exists()) {
            abort(403);
        }
        app(CurrentTenant::class)->set($tenant);

        return $next($request);
    }
}
