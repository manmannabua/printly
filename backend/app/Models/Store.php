<?php

namespace App\Models;

use App\Services\PlanCatalog;
use App\Traits\HasSafeEncryptedAttributes;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
     * Whether this store can take online payments: its plan unlocks the feature,
     * payments are switched on, and a PayMongo secret is configured. Gating
     * online_payments here is what makes it a paid (Pro+) capability.
     */
    public function acceptsOnlinePayments(): bool
    {
        return $this->allows('online_payments')
            && $this->payments_enabled
            && ! empty($this->paymongo_secret_key);
    }

    /**
     * Whether auto-print is live for this store: the plan includes it AND the
     * store has switched it on (settings.auto_print). The plan gate is the
     * revenue lever — auto-print is the headline "Auto" upsell (planning §5.3).
     */
    public function autoPrintEnabled(): bool
    {
        return $this->allows('auto_print')
            && (bool) ($this->settings['auto_print'] ?? false);
    }

    /**
     * The store's Printly subscription (its current plan). May be absent for
     * legacy rows created before the subscription module — callers treat that
     * as "the plan column is authoritative and live".
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    /**
     * The plan whose features actually apply right now. A store keeps its chosen
     * plan on the `plan` column, but a lapsed subscription (past_due/canceled)
     * falls back to the default plan's feature set until it's brought current.
     */
    public function effectivePlan(): string
    {
        $sub = $this->relationLoaded('subscription') ? $this->subscription : $this->subscription()->first();

        if ($sub && ! $sub->isLive()) {
            return PlanCatalog::default();
        }

        return PlanCatalog::isValid($this->plan) ? $this->plan : PlanCatalog::default();
    }

    /**
     * Feature keys the store is currently entitled to.
     *
     * @return array<int, string>
     */
    public function planFeatures(): array
    {
        return PlanCatalog::features($this->effectivePlan());
    }

    /**
     * The plan gate: whether the store may use a given feature right now.
     */
    public function allows(string $feature): bool
    {
        return PlanCatalog::has($this->effectivePlan(), $feature);
    }

    /**
     * A numeric plan limit (e.g. 'staff_seats', 'products'), or null = unlimited.
     */
    public function planLimit(string $key): ?int
    {
        return PlanCatalog::limit($this->effectivePlan(), $key);
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

    public function printAgents(): HasMany
    {
        return $this->hasMany(PrintAgent::class);
    }

    public function printers(): HasMany
    {
        return $this->hasMany(Printer::class);
    }

    public function printJobs(): HasMany
    {
        return $this->hasMany(PrintJob::class);
    }
}
