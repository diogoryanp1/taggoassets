<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Models\User;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;

    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }

    protected $fillable = ['name', 'slug', 'document_type', 'document_number', 'status', 'timezone', 'locale'];

    protected static function booted(): void
    {
        static::creating(fn (self $tenant) => $tenant->public_id ??= (string) Str::ulid());
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'tenant_user')->withPivot(['role_id', 'status'])->withTimestamps();
    }

    public function settings()
    {
        return $this->hasMany(TenantSetting::class);
    }

    public function features()
    {
        return $this->hasMany(TenantFeature::class);
    }

    public function units()
    {
        return $this->hasMany(OrganizationalUnit::class);
    }
}
