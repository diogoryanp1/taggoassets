<?php

namespace App\Domain\Audit;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Audit\Services\AuditPayloadSanitizer;
use App\Domain\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class AuditLogger
{
    public function record(string $action, ?Model $entity = null, array $old = [], array $new = [], ?string $reason = null): void
    {
        $request = app()->bound('request') ? app(Request::class) : null;
        $tenant = app(CurrentTenant::class)->get();
        AuditLog::create([
            'tenant_id' => $tenant?->id, 'user_id' => $request?->user()?->id, 'action' => $action,
            'entity_type' => $entity ? $entity::class : null, 'entity_id' => $entity?->getKey(),
            'old_values' => app(AuditPayloadSanitizer::class)->sanitize($old), 'new_values' => app(AuditPayloadSanitizer::class)->sanitize($new),
            'ip_address' => $request?->ip(), 'user_agent' => $request?->userAgent(),
            'request_id' => $request?->attributes->get('request_id'), 'reason' => $reason,
        ]);
    }
}
