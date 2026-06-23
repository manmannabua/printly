<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolePermissionAudit extends Model
{
    use HasUuid;

    const UPDATED_AT = null;

    protected $fillable = [
        'role_id',
        'actor_id',
        'added_permissions',
        'removed_permissions',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'added_permissions' => 'array',
        'removed_permissions' => 'array',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
