<?php

namespace App\Domain\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;

class TenantFeature extends Model
{
    protected $fillable = ['tenant_id', 'feature', 'enabled'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }
}
