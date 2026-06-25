<?php

namespace App\Models;

use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Models\UserSession;
use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\Models\Tenant;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(fn (self $user) => $user->public_id ??= (string) Str::ulid());
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'blocked_at' => 'datetime',
            'is_platform_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /** @return BelongsToMany<Tenant, $this> */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user')->withPivot(['role_id', 'status'])->withTimestamps();
    }

    /** @return BelongsToMany<OrganizationalUnit, $this> */
    public function organizationalUnits(): BelongsToMany
    {
        return $this->belongsToMany(OrganizationalUnit::class, 'user_organizational_units');
    }

    /** @return HasMany<UserSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function hasPermission(Tenant $tenant, string $permission): bool
    {
        if ($this->is_platform_admin) {
            return true;
        }
        $membership = $this->tenants()->whereKey($tenant->id)->first();
        if (! $membership) {
            return false;
        }
        $pivot = $membership->getRelation('pivot');
        if (! $pivot instanceof Pivot) {
            return false;
        }
        $status = $pivot->getAttribute('status');
        $roleId = $pivot->getAttribute('role_id');
        if ($status !== 'active' || (! is_int($roleId) && (! is_string($roleId) || ! ctype_digit($roleId)))) {
            return false;
        }
        $role = Role::query()->with('permissions')->whereKey((int) $roleId)->first();

        return $role?->name === 'super_admin' || $role?->permissions->contains('name', $permission);
    }
}
