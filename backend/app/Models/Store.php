<?php

namespace App\Models;

use App\Traits\HasSafeEncryptedAttributes;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory, HasSafeEncryptedAttributes, HasUuid, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'plan',
        'status',
        'timezone',
        'currency',
        'lat',
        'lng',
        'address',
        'settings',
        'paymongo_secret_key',
        'paymongo_webhook_secret',
        'payments_enabled',
    ];

    protected $casts = [
        'settings' => 'array',
        'lat' => 'float',
        'lng' => 'float',
        'payments_enabled' => 'boolean',
        // PSP secrets are encrypted at rest (safe-decrypt trait handles failures).
        'paymongo_secret_key' => 'encrypted',
        'paymongo_webhook_secret' => 'encrypted',
    ];

    protected $hidden = [
        'paymongo_secret_key',
        'paymongo_webhook_secret',
    ];

    /**
     * Whether this store can take online payments (enabled + secret configured).
     */
    public function acceptsOnlinePayments(): bool
    {
        return $this->payments_enabled && ! empty($this->paymongo_secret_key);
    }

    /**
     * Staff/owners with access to this store.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function productTypes(): HasMany
    {
        return $this->hasMany(ProductType::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
