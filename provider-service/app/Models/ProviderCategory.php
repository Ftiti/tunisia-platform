<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderCategory extends Model
{
    protected $fillable = ['name','slug','icon','color','description','parent_id','order','is_active'];
    protected $casts = ['is_active' => 'boolean', 'order' => 'integer'];

    public function providers(): HasMany { return $this->hasMany(Provider::class, 'category_id'); }
    public function children(): HasMany  { return $this->hasMany(ProviderCategory::class, 'parent_id'); }
    public function parent(): BelongsTo  { return $this->belongsTo(ProviderCategory::class, 'parent_id'); }
    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeRoots($q)  { return $q->whereNull('parent_id'); }
}
