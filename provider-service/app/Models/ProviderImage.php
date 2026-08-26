<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderImage extends Model
{
    protected $table = 'provider_images';
    protected $fillable = ['provider_id','url','alt','is_primary','order'];
    protected $casts = ['is_primary' => 'boolean', 'order' => 'integer'];

    public function provider(): BelongsTo { return $this->belongsTo(Provider::class); }
}
