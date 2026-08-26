<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderProduct extends Model
{
    protected $fillable = [
        'provider_id', 'product_category_id', 'name', 'slug', 'description',
        'price', 'price_promo', 'stock', 'image', 'is_active', 'is_featured', 'variants',
    ];

    protected function casts(): array
    {
        return [
            'price'       => 'float',
            'price_promo' => 'float',
            'stock'       => 'integer',
            'is_active'   => 'boolean',
            'is_featured' => 'boolean',
            'variants'    => 'array',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function getIsInStockAttribute(): bool
    {
        return $this->stock === -1 || $this->stock > 0;
    }

    public function getIsPromoAttribute(): bool
    {
        return $this->price_promo !== null && $this->price_promo < $this->price;
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeInStock($q)
    {
        return $q->where(fn($q) => $q->where('stock', -1)->orWhere('stock', '>', 0));
    }
}
