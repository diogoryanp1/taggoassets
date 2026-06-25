<?php

namespace Database\Factories;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AuditLog> */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return ['tenant_id' => Tenant::factory(), 'user_id' => User::factory(), 'action' => 'resource.created', 'entity_type' => User::class, 'entity_id' => 1, 'old_values' => [], 'new_values' => [], 'ip_address' => '127.0.0.1', 'request_id' => (string) Str::ulid()];
    }
}
