<?php

namespace App\Http\Controllers;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Tenancy\CurrentTenant;
use App\Support\Pagination\PaginationResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request, CurrentTenant $currentTenant, PaginationResolver $pagination): View
    {
        $this->authorize('viewAny', AuditLog::class);
        $filtered = $request->filled('from') || $request->filled('action') || $request->filled('request_id') || $request->filled('user');
        $logs = null;
        if ($filtered) {
            $query = AuditLog::query()->where('tenant_id', $currentTenant->id())->select(['public_id', 'user_id', 'action', 'entity_type', 'entity_id', 'ip_address', 'request_id', 'created_at'])->with('user:id,name');
            if ($request->filled('from')) {
                $query->whereBetween('created_at', [$request->date('from')->startOfDay(), $request->date('to', now())->endOfDay()]);
            }
            if ($request->filled('action')) {
                $query->where('action', $request->string('action'));
            }
            if ($request->filled('request_id')) {
                $query->where('request_id', $request->string('request_id'));
            }
            if ($request->filled('user')) {
                $query->where('user_id', $request->integer('user'));
            }
            $logs = $query->latest('created_at')->paginate($pagination->resolve($request))->withQueryString();
        }

        return view('audit.index', compact('logs', 'filtered'));
    }

    public function show(AuditLog $auditLog, CurrentTenant $currentTenant): View
    {
        abort_unless($auditLog->tenant_id === $currentTenant->id(), 404);
        $this->authorize('viewAny', AuditLog::class);

        return view('audit.show', compact('auditLog'));
    }
}
