<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Provider extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'name', 'slug', 'description',
        'address', 'city', 'governorate', 'postal_code',
        'phone', 'email', 'website', 'rating', 'total_reviews',
        'is_active', 'is_verified', 'is_featured', 'metadata',
        'validation_status', 'type', 'delivery_type', 'delivery_radius_km',
        'delivery_cities', 'commission_rate', 'cover_image',
    ];

    protected $casts = [
        'metadata'           => 'array',
        'is_active'          => 'boolean',
        'is_verified'        => 'boolean',
        'is_featured'        => 'boolean',
        'rating'             => 'float',
        'total_reviews'      => 'integer',
        'validation_status'  => 'string',
        'type'               => 'string',
        'delivery_type'      => 'string',
        'delivery_radius_km' => 'integer',
        'delivery_cities'    => 'array',
        'commission_rate'    => 'float',
    ];

    // ── Accessors PostGIS ─────────────────────────────────────────────────────
    public function getLatitudeAttribute(): ?float
    {
        if (!$this->location) return null;
        preg_match('/POINT\(([^ ]+) ([^ ]+)\)/', $this->location, $m);
        return isset($m[2]) ? (float) $m[2] : null;
    }

    public function getLongitudeAttribute(): ?float
    {
        if (!$this->location) return null;
        preg_match('/POINT\(([^ ]+) ([^ ]+)\)/', $this->location, $m);
        return isset($m[1]) ? (float) $m[1] : null;
    }

    public function getOpenStatusAttribute(): string
    {
        $s = $this->schedules()->where('day_of_week', now()->dayOfWeek)->first();
        if (!$s || $s->is_closed) return 'closed';
        $now = now()->format('H:i:s');
        return ($now >= $s->open_time && $now <= $s->close_time) ? 'open' : 'closed';
    }

    // ── Relations ─────────────────────────────────────────────────────────────
    public function category(): BelongsTo     { return $this->belongsTo(ProviderCategory::class, 'category_id'); }
    public function services(): HasMany       { return $this->hasMany(ProviderService::class); }
    public function schedules(): HasMany      { return $this->hasMany(ProviderSchedule::class)->orderBy('day_of_week'); }
    public function reviews(): HasMany        { return $this->hasMany(ProviderReview::class)->latest(); }
    public function images(): HasMany         { return $this->hasMany(ProviderImage::class)->orderBy('order'); }
    public function subscription(): HasOne    { return $this->hasOne(ProviderSubscription::class); }
    public function products(): HasMany       { return $this->hasMany(ProviderProduct::class); }

    // ── Scopes ────────────────────────────────────────────────────────────────
    public function scopeActive(Builder $q): Builder      { return $q->where('is_active', true); }
    public function scopeVerified(Builder $q): Builder    { return $q->where('is_verified', true); }
    public function scopeFeatured(Builder $q): Builder    { return $q->where('is_featured', true); }
    public function scopeByCity(Builder $q, string $c): Builder { return $q->where('city', 'ILIKE', "%{$c}%"); }
    public function scopeByCategory(Builder $q, int $id): Builder { return $q->where('category_id', $id); }
    public function scopeByGovernorate(Builder $q, string $g): Builder { return $q->where('governorate', 'ILIKE', "%{$g}%"); }
    public function scopeValidated(Builder $q): Builder   { return $q->where('validation_status', 'validated'); }
    public function scopeByType(Builder $q, string $type): Builder { return $q->where('type', $type); }

    public function scopeNearby(Builder $q, float $lat, float $lng, float $radius = 10): Builder
    {
        return $q->selectRaw(
            "providers.*, ST_Distance(location::geography, ST_SetSRID(ST_Point(?,?)::geography,4326))/1000 AS distance_km",
            [$lng, $lat]
        )->whereRaw(
            "location IS NOT NULL AND ST_DWithin(location::geography, ST_SetSRID(ST_Point(?,?)::geography,4326), ?)",
            [$lng, $lat, $radius * 1000]
        )->orderBy('distance_km');
    }

    // ── Métier ────────────────────────────────────────────────────────────────
    public function isOpenNow(): bool { return $this->open_status === 'open'; }

    public function updateRating(): void
    {
        $avg = $this->reviews()->avg('rating') ?? 0;
        $this->update(['rating' => round($avg, 2), 'total_reviews' => $this->reviews()->count()]);
    }

    public function canDeliver(string $city): bool
    {
        if ($this->delivery_type === 'none') return false;
        if ($this->delivery_type === 'national') return true;
        return in_array(strtolower($city), array_map('strtolower', $this->delivery_cities ?? []));
    }

    public function calculateCommission(float $amount): float
    {
        return round($amount * $this->commission_rate / 100, 3);
    }
}
