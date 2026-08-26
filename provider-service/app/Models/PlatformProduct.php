<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformProduct extends Model
{
    protected $fillable = [
        'platform_category_id', 'name', 'slug', 'description',
        'price', 'price_promo', 'stock', 'image',
        'is_active', 'is_featured', 'is_promo', 'is_vedette',
        'delivery_type', 'variants',
    ];

    protected function casts(): array
    {
        return [
            'price'       => 'float',
            'price_promo' => 'float',
            'stock'       => 'integer',
            'is_active'   => 'boolean',
            'is_featured' => 'boolean',
            'is_promo'    => 'boolean',
            'is_vedette'  => 'boolean',
            'variants'    => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PlatformCategory::class, 'platform_category_id');
    }

    public function getIsInStockAttribute(): bool
    {
        return $this->stock === -1 || $this->stock > 0;
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopePromo($q)
    {
        return $q->where('is_promo', true);
    }

    public function scopeVedette($q)
    {
        return $q->where('is_vedette', true);
    }
}
