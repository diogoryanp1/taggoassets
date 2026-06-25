<?php

namespace App\Http\Controllers;

use App\Domain\Audit\AuditLogger;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantSelectionController
{
    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate(['tenant' => ['required', 'string', 'size:26']]);
        $tenant = Tenant::where('public_id', $data['tenant'])->where('status', 'active')->firstOrFail();
        abort_unless($request->user()->tenants()->whereKey($tenant->id)->wherePivot('status', 'active')->exists(), 404);
        $request->session()->put('active_tenant', $tenant->public_id);
        $audit->record('tenant.changed', $tenant);

        return back();
    }
}
