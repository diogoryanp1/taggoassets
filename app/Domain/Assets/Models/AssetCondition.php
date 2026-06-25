<?php

namespace App\Domain\Assets\Models;

use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AssetCondition extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'description', 'sort_order', 'is_system', 'is_active'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_system' => 'boolean', 'is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->public_id ??= (string) Str::ulid());
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @param Builder<AssetCondition> $query */
    public function scopeAvailableToTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where(fn (Builder $query) => $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId));
    }
}
