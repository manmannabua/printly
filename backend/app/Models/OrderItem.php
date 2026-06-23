<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'order_id',
        'store_id',
        'product_id',
        'product_name',
        'pricing_mode',
        'quantity',
        'unit_breakdown',
        'spec_selections',
        'line_total_cents',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_breakdown' => 'array',
        'spec_selections' => 'array',
        'line_total_cents' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(OrderFile::class);
    }
}
