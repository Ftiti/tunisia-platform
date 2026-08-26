<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleException extends Model
{
    protected $fillable = [
        'provider_id', 'date', 'is_closed',
        'open_time', 'close_time', 'reason',
    ];

    protected function casts(): array
    {
        return [
            'date'      => 'date',
            'is_closed' => 'boolean',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}