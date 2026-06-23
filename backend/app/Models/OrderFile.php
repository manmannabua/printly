<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderFile extends Model
{
    use HasFactory, HasUuid;

    public const ANALYSIS_PENDING = 'pending';

    public const ANALYSIS_DONE = 'done';

    public const ANALYSIS_FAILED = 'failed';

    protected $fillable = [
        'store_id',
        'order_item_id',
        'customer_id',
        'original_name',
        'mime',
        'size_bytes',
        'storage_path',
        'page_count',
        'paper_size',
        'is_color',
        'color_pages',
        'preview_path',
        'analysis_status',
        'analysis_error',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'page_count' => 'integer',
        'color_pages' => 'integer',
        'is_color' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
