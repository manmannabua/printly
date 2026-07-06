<?php

namespace App\Models;

use App\Services\PlanCatalog;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A store's current Printly plan (see planning §4.4). One row per store; the
 * denormalised `stores.plan` column mirrors this row's plan so feature gates
 * can stay cheap (no join). SubscriptionService keeps the two in step.
 */
class Subscription extends Model
{
    use HasUuid;

    public const STATUS_TRIALING = 'trialing';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAST_DUE = 'past_due';
    public const STATUS_CANCELED = 'canceled';

    /** Statuses that still grant plan features (a lapsed store falls back to starter). */
    public const LIVE = [self::STATUS_TRIALING, self::STATUS_ACTIVE];

    protected $fillable = [
        'store_id',
        'plan',
        'price_cents',
        'status',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'canceled_at',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** Whether the subscription currently entitles the store to its plan's features. */
    public function isLive(): bool
    {
        return in_array($this->status, self::LIVE, true);
    }

    public function planName(): string
    {
        return PlanCatalog::name($this->plan);
    }
}
