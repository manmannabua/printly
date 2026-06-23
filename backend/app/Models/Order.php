<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Order extends Model
{
    use HasFactory, HasUuid;

    // Lifecycle states (planning §3).
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_PAID = 'paid';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_READY = 'ready';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    public const PAYMENT_UNPAID = 'unpaid';

    public const PAYMENT_AUTHORIZED = 'authorized';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_REFUNDED = 'refunded';

    protected $fillable = [
        'store_id',
        'customer_id',
        'code',
        'status',
        'subtotal_cents',
        'fee_cents',
        'total_cents',
        'payment_status',
        'pay_method',
        'placed_at',
        'accepted_at',
        'ready_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'subtotal_cents' => 'integer',
        'fee_cents' => 'integer',
        'total_cents' => 'integer',
        'placed_at' => 'datetime',
        'accepted_at' => 'datetime',
        'ready_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->orderBy('created_at');
    }

    public function files(): HasManyThrough
    {
        return $this->hasManyThrough(OrderFile::class, OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
