<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A physical printer an agent drives. `capabilities` records the paper sizes it
 * can handle and whether it prints colour, so a job can be routed to a printer
 * that can actually print it.
 *
 * capabilities: { "sizes": ["A4","Letter"], "color": false }
 */
class Printer extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'store_id',
        'print_agent_id',
        'name',
        'capabilities',
        'is_active',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'is_active' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(PrintAgent::class, 'print_agent_id');
    }

    public function printJobs(): HasMany
    {
        return $this->hasMany(PrintJob::class);
    }

    /**
     * Whether this printer can handle the given paper size (and colour, if the
     * job needs it). A printer with no declared sizes accepts anything; a null
     * paper size (unknown) is always accepted so a job is never silently dropped.
     */
    public function canPrint(?string $paperSize, bool $needsColor = false): bool
    {
        $caps = $this->capabilities ?? [];

        if ($needsColor && array_key_exists('color', $caps) && ! $caps['color']) {
            return false;
        }

        $sizes = $caps['sizes'] ?? [];
        if (empty($sizes) || $paperSize === null) {
            return true;
        }

        return in_array($paperSize, $sizes, true);
    }
}
