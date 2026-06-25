<?php

namespace App\Domain\Assets\Models;

use App\Domain\Assets\Enums\AssetStatus;
use App\Domain\Organizations\Models\Location;
use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = ['asset_number', 'legacy_number', 'description', 'asset_category_id', 'asset_type_id', 'brand_id', 'model_id', 'unit_of_measure_id', 'condition_id', 'status', 'organizational_unit_id', 'location_id', 'custodian_id', 'acquisition_date', 'acquisition_value_cents', 'serial_number', 'notes', 'custom_values', 'is_active', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['status' => AssetStatus::class, 'acquisition_date' => 'date', 'acquisition_value_cents' => 'integer', 'custom_values' => 'array', 'is_active' => 'boolean'];
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

    /** @return BelongsTo<AssetType, $this> */
    public function type(): BelongsTo
    {
        return $this->belongsTo(AssetType::class, 'asset_type_id');
    }

    /** @return BelongsTo<AssetBrand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(AssetBrand::class, 'brand_id');
    }

    /** @return BelongsTo<AssetModel, $this> */
    public function model(): BelongsTo
    {
        return $this->belongsTo(AssetModel::class, 'model_id');
    }

    /** @return BelongsTo<UnitOfMeasure, $this> */
    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    /** @return BelongsTo<AssetCondition, $this> */
    public function condition(): BelongsTo
    {
        return $this->belongsTo(AssetCondition::class, 'condition_id');
    }

    /** @return BelongsTo<OrganizationalUnit, $this> */
    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @return BelongsTo<AssetCustodian, $this> */
    public function custodian(): BelongsTo
    {
        return $this->belongsTo(AssetCustodian::class);
    }

    /** @return HasMany<AssetMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(AssetMovement::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @param Builder<Asset> $query */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }
}
