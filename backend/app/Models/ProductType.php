<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductType extends Model
{
    use HasFactory, HasUuid;

    public const PRICING_FILE_BASED = 'file_based';
    public const PRICING_SPEC_BASED = 'spec_based';

    protected $fillable = [
        'store_id',
        'name',
        'pricing_mode',
        'fulfillment',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function isFileBased(): bool
    {
        return $this->pricing_mode === self::PRICING_FILE_BASED;
    }
}
