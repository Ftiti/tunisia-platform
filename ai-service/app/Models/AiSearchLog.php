<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSearchLog extends Model
{
    protected $fillable = [
        'user_id',
        'query',
        'parsed_filters',
        'results_count',
        'response_time_ms',
    ];

    protected $casts = [
        'parsed_filters'   => 'array',
        'results_count'    => 'integer',
        'response_time_ms' => 'integer',
    ];
}
