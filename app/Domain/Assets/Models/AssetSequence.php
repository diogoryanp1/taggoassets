<?php

namespace App\Domain\Assets\Models;

use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetSequence extends Model
{
    use HasFactory;

    protected $table = 'asset_number_sequences';

    protected $fillable = ['tenant_id', 'year', 'next_value'];

    protected function casts(): array
    {
        return ['year' => 'integer', 'next_value' => 'integer'];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
