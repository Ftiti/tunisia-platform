<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderService extends Model
{
    protected $table = 'provider_services';
    protected $fillable = ['provider_id','name','description','price','price_max','duration_minutes','unit','is_active'];
    protected $casts = ['price' => 'float', 'price_max' => 'float', 'duration_minutes' => 'integer', 'is_active' => 'boolean'];

    public function provider(): BelongsTo { return $this->belongsTo(Provider::class); }

    public function getFormattedPriceAttribute(): string
    {
        $p = number_format($this->price, 3).' DT';
        if ($this->price_max) $p .= ' – '.number_format($this->price_max, 3).' DT';
        return $p;
    }

    public function getFormattedDurationAttribute(): string
    {
        $h = intdiv($this->duration_minutes, 60);
        $m = $this->duration_minutes % 60;
        if ($h > 0 && $m > 0) return "{$h}h{$m}min";
        return $h > 0 ? "{$h}h" : "{$m}min";
    }
}
