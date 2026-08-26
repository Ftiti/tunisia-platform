<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiRecommendation extends Model
{
    protected $fillable = [
        'user_id',
        'provider_ids',
        'reason',
        'expires_at',
    ];

    protected $casts = [
        'provider_ids' => 'array',
        'expires_at'   => 'datetime',
    ];
}
