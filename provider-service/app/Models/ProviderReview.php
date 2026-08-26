<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderReview extends Model
{
    protected $table = 'provider_reviews';
    protected $fillable = ['provider_id','user_id','booking_id','rating','comment','is_verified'];
    protected $casts = ['rating' => 'integer', 'is_verified' => 'boolean'];

    public function provider(): BelongsTo { return $this->belongsTo(Provider::class); }
    public function scopeVerified($q) { return $q->where('is_verified', true); }
}
