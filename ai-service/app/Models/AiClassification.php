<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiClassification extends Model
{
    protected $fillable = [
        'provider_id',
        'result',
        'confidence',
        'is_applied',
    ];

    protected $casts = [
        'result'     => 'array',
        'confidence' => 'float',
        'is_applied' => 'boolean',
    ];
}
