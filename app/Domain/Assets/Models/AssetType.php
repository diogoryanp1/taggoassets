<?php

namespace App\Domain\Assets\Models;

use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AssetType extends Model
{
    use HasFactory;

    protected $fillable = ['asset_category_id', 'name', 'name_normalized', 'code', 'description', 'is_active', 'requires_serial_number', 'requires_brand', 'requires_model', 'is_depreciable', 'default_useful_life_months', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'requires_serial_number' => 'boolean', 'requires_brand' => 'boolean', 'requires_model' => 'boolean', 'is_depreciable' => 'boolean', 'default_useful_life_months' => 'integer'];
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

    /** @return BelongsTo<AssetCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    /** @param Builder<AssetType> $query */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }
}
