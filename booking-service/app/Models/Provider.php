<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    protected $fillable = [
        'user_id', 'name', 'category_id', 'description',
        'address', 'city', 'location', 'phone', 'email',
        'rating', 'total_reviews', 'is_active', 'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'is_active'     => 'boolean',
            'is_verified'   => 'boolean',
            'rating'        => 'float',
            'total_reviews' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function scheduleExceptions(): HasMany
    {
        return $this->hasMany(ScheduleException::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** Scope: prestataires actifs */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Scope: prestataires vérifiés */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }
}