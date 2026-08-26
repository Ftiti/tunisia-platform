<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderSchedule extends Model
{
    protected $table = 'provider_schedules';
    protected $fillable = ['provider_id','day_of_week','open_time','close_time','break_start','break_end','is_closed'];
    protected $casts = ['is_closed' => 'boolean', 'day_of_week' => 'integer'];

    public function provider(): BelongsTo { return $this->belongsTo(Provider::class); }

    public function getDayNameAttribute(): string
    {
        return ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'][$this->day_of_week] ?? '?';
    }
}
