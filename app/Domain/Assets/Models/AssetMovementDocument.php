<?php

namespace App\Domain\Assets\Models;

use App\Domain\Assets\Enums\AssetMovementDocumentType;
use App\Domain\Documents\Models\PrivateDocument;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AssetMovementDocument extends Model
{
    use HasFactory;

    protected $fillable = ['document_type', 'deactivated_at', 'deactivated_by'];

    protected function casts(): array
    {
        return ['document_type' => AssetMovementDocumentType::class, 'deactivated_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $document) => $document->public_id ??= (string) Str::ulid());
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

    /** @return BelongsTo<AssetMovement, $this> */
    public function movement(): BelongsTo
    {
        return $this->belongsTo(AssetMovement::class, 'asset_movement_id');
    }

    /** @return BelongsTo<PrivateDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(PrivateDocument::class, 'private_document_id');
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** @param Builder<AssetMovementDocument> $query */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function documentType(): AssetMovementDocumentType
    {
        return AssetMovementDocumentType::from((string) $this->getRawOriginal('document_type'));
    }
}
