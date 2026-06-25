<?php

namespace App\Domain\Assets\Models;

use App\Domain\Assets\Enums\AssetMovementStatus;
use App\Domain\Assets\Enums\AssetMovementType;
use App\Domain\Documents\Models\PrivateDocument;
use App\Domain\Organizations\Models\Location;
use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AssetMovement extends Model
{
    use HasFactory;

    protected $fillable = ['movement_type', 'status', 'destination_organizational_unit_id', 'destination_location_id', 'destination_custodian_id', 'expected_return_at', 'returned_at', 'reason', 'notes', 'metadata'];

    protected function casts(): array
    {
        return [
            'movement_type' => AssetMovementType::class,
            'status' => AssetMovementStatus::class,
            'approved_at' => 'datetime',
            'effective_at' => 'datetime',
            'expected_return_at' => 'datetime',
            'returned_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $movement) => $movement->public_id ??= (string) Str::ulid());
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

    /** @return BelongsTo<Asset, $this> */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /** @return BelongsTo<OrganizationalUnit, $this> */
    public function originUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'origin_organizational_unit_id');
    }

    /** @return BelongsTo<OrganizationalUnit, $this> */
    public function destinationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'destination_organizational_unit_id');
    }

    /** @return BelongsTo<Location, $this> */
    public function originLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'origin_location_id');
    }

    /** @return BelongsTo<Location, $this> */
    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'destination_location_id');
    }

    /** @return BelongsTo<AssetCustodian, $this> */
    public function originCustodian(): BelongsTo
    {
        return $this->belongsTo(AssetCustodian::class, 'origin_custodian_id');
    }

    /** @return BelongsTo<AssetCustodian, $this> */
    public function destinationCustodian(): BelongsTo
    {
        return $this->belongsTo(AssetCustodian::class, 'destination_custodian_id');
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return BelongsTo<PrivateDocument, $this> */
    public function termDocument(): BelongsTo
    {
        return $this->belongsTo(PrivateDocument::class, 'term_document_id');
    }

    /** @return HasMany<AssetMovementDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(AssetMovementDocument::class);
    }

    public function isReturnOverdue(): bool
    {
        $expectedReturn = $this->getRawOriginal('expected_return_at');

        return $expectedReturn !== null && $this->returned_at === null && Carbon::parse((string) $expectedReturn)->isPast() && in_array($this->movementType(), [AssetMovementType::TemporaryCheckout, AssetMovementType::Loan], true);
    }

    public function movementType(): AssetMovementType
    {
        return AssetMovementType::from((string) $this->getRawOriginal('movement_type'));
    }

    public function movementStatus(): AssetMovementStatus
    {
        return AssetMovementStatus::from((string) $this->getRawOriginal('status'));
    }

    /** @param Builder<AssetMovement> $query */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }
}
