<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceRule extends Model
{
    use HasFactory, HasUuid;

    public const MOD_PER_PAGE = 'per_page';
    public const MOD_PER_JOB = 'per_job';
    public const MOD_MULTIPLIER = 'multiplier';

    /**
     * Attributes the pricing engine actually matches against a file spec
     * (see PricingService::quoteFileBased). A rule on any other attribute would
     * silently never fire, so creation is constrained to this set.
     *
     * @var array<int, string>
     */
    public const SUPPORTED_ATTRIBUTES = ['paper_size', 'color', 'duplex'];

    protected $fillable = [
        'store_id',
        'product_id',
        'attribute',
        'match_value',
        'modifier_type',
        'amount_cents',
        'multiplier',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'multiplier' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
