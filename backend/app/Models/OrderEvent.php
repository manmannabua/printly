<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only state-change log. One row per transition; never updated.
 */
class OrderEvent extends Model
{
    use HasFactory, HasUuid;

    const UPDATED_AT = null;

    public const ACTOR_SYSTEM = 'system';

    public const ACTOR_STAFF = 'staff';

    public const ACTOR_CUSTOMER = 'customer';

    protected $fillable = [
        'order_id',
        'store_id',
        'from_status',
        'to_status',
        'actor_type',
        'actor_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
