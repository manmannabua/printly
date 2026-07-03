<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One print job per printable file. Created when an order is accepted (if the
 * store has auto-print on); the agent claims it (queued → sent), prints
 * (printing → done) or fails (error), and the aggregate flows back into the
 * order state machine (accepted → in_progress → ready).
 */
class PrintJob extends Model
{
    use HasFactory, HasUuid;

    public const STATUS_QUEUED = 'queued';    // created, waiting for an agent to claim

    public const STATUS_SENT = 'sent';        // claimed by the agent, downloading/spooling

    public const STATUS_PRINTING = 'printing'; // physically printing

    public const STATUS_DONE = 'done';        // printed successfully

    public const STATUS_ERROR = 'error';      // failed; staff may retry

    /** Statuses that count as finished (no further agent action). */
    public const TERMINAL = [self::STATUS_DONE, self::STATUS_ERROR];

    /** Statuses the agent has actively started working. */
    public const ACTIVE = [self::STATUS_SENT, self::STATUS_PRINTING];

    /**
     * A claimed job (sent/printing) whose last update is older than this is
     * considered stale — the agent likely crashed — and may be reclaimed on the
     * next poll or retried by the owner, so a job never gets stuck forever.
     */
    public const STALE_AFTER_SECONDS = 180;

    protected $fillable = [
        'store_id',
        'order_id',
        'order_item_id',
        'order_file_id',
        'printer_id',
        'status',
        'copies',
        'error',
        'attempts',
        'sent_at',
        'printed_at',
    ];

    protected $casts = [
        'copies' => 'integer',
        'attempts' => 'integer',
        'sent_at' => 'datetime',
        'printed_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function orderFile(): BelongsTo
    {
        return $this->belongsTo(OrderFile::class);
    }

    public function printer(): BelongsTo
    {
        return $this->belongsTo(Printer::class);
    }
}
