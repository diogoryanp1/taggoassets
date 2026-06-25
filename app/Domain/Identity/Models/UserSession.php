<?php

namespace App\Domain\Identity\Models;

use App\Models\User;
use Database\Factories\UserSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UserSession extends Model
{
    use HasFactory;

    protected static function newFactory(): UserSessionFactory
    {
        return UserSessionFactory::new();
    }

    protected $fillable = ['user_id', 'session_id_encrypted', 'session_fingerprint', 'ip_address', 'user_agent', 'last_activity_at', 'revoked_at'];

    protected $hidden = ['session_id_encrypted', 'session_fingerprint'];

    protected function casts(): array
    {
        return ['session_id_encrypted' => 'encrypted', 'last_activity_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->public_id ??= (string) Str::ulid());
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
