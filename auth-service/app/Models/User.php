<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'avatar',
        'is_active',
        'permissions',
        'validation_status',
        'provider_type',
        'rejection_reason',
        'validated_at',
        'settings',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'permissions'       => 'array',
            'validation_status' => 'string',
            'provider_type'     => 'string',
            'validated_at'      => 'datetime',
            'settings'          => 'array',
        ];
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    // ─── Helpers de rôle ─────────────────────────────────────────────────────

    public function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];
        return in_array($this->role, $roles);
    }

    public function isAdmin(): bool    { return $this->role === 'admin'; }
    public function isProvider(): bool { return $this->role === 'provider'; }
    public function isClient(): bool   { return $this->role === 'client'; }
    public function isDelivery(): bool { return $this->role === 'delivery'; }

    // ─── Helpers de statut de validation ─────────────────────────────────────

    public function isPending(): bool    { return $this->validation_status === 'pending'; }
    public function isValidated(): bool  { return $this->validation_status === 'validated'; }
    public function isRejected(): bool   { return $this->validation_status === 'rejected'; }
    public function isSuspended(): bool  { return $this->validation_status === 'suspended'; }

    // ─── Relations ───────────────────────────────────────────────────────────

    public function roleModel(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role', 'name');
    }
}
