<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'is_guest',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_guest' => 'boolean',
        'password' => 'hashed',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
