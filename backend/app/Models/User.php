<?php

namespace App\Models;

use App\Traits\HasPermissions;
use App\Traits\HasRoles;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasPermissions, HasRoles, HasUuid, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
        'is_active',
        'email_verified_at',
        'last_login_at',
        'security_pin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'security_pin',
    ];

    /**
     * The attributes that should be appended to the model.
     *
     * @var list<string>
     */
    protected $appends = [
        'is_admin',
        'primary_role',
        'has_security_pin',
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
            'password' => 'hashed',
            'security_pin' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Check if the user has a security PIN set.
     */
    public function getHasSecurityPinAttribute(): bool
    {
        return !empty($this->security_pin);
    }

    /**
     * Update the last login timestamp.
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Stores this user is a member of (staff/owner). Admins bypass membership.
     */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'store_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * IDs of the stores this user belongs to.
     *
     * @return array<int, string>
     */
    public function storeIds(): array
    {
        return $this->stores()->pluck('stores.id')->all();
    }

    /**
     * Whether this user may access the given store. Admins always may.
     */
    public function belongsToStore(Store|string $store): bool
    {
        if ($this->is_admin) {
            return true;
        }

        $storeId = $store instanceof Store ? $store->id : $store;

        return $this->stores()->whereKey($storeId)->exists();
    }
}
