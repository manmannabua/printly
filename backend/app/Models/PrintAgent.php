<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A local print agent installed in a store (biometric-bridge pattern). It
 * authenticates with a bearer token (stored only as a sha256 hash), polls for
 * queued print jobs, drives one or more physical printers, and reports status.
 */
class PrintAgent extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'store_id',
        'name',
        'token_hash',
        'last_seen_at',
        'is_active',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function printers(): HasMany
    {
        return $this->hasMany(Printer::class);
    }

    /**
     * Generate a fresh plaintext token. Shown to the owner exactly once; only
     * its hash is persisted.
     */
    public static function newToken(): string
    {
        return 'pa_'.Str::random(48);
    }

    /**
     * The stored form of a plaintext token (constant-time comparable, indexable).
     */
    public static function hashToken(string $plain): string
    {
        return hash('sha256', $plain);
    }
}
