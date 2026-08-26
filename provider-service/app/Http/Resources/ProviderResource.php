<?php
namespace App\Http\Resources;

use App\Services\ProviderAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'slug'          => $this->slug,
            'description'   => $this->description,
            'address'       => $this->address,
            'city'          => $this->city,
            'governorate'   => $this->governorate,
            'postal_code'   => $this->postal_code,
            'phone'         => $this->phone,
            'email'         => $this->email,
            'website'       => $this->website,
            'rating'        => round($this->rating, 2),
            'total_reviews' => $this->total_reviews,
            'is_active'     => $this->is_active,
            'is_verified'   => $this->is_verified,
            'is_featured'   => $this->is_featured,
            'latitude'      => $this->latitude,
            'longitude'     => $this->longitude,
            'distance'      => $this->when(isset($this->distance_km), fn() => round((float)$this->distance_km, 1).' km'),
            'distance_km'   => $this->when(isset($this->distance_km), fn() => round((float)$this->distance_km, 2)),
            'open_status'   => $this->when(
                $this->relationLoaded('schedules'),
                fn() => app(ProviderAvailabilityService::class)->getOpenStatus($this->resource)
            ),
            'category'      => new CategoryResource($this->whenLoaded('category')),
            'services'      => ProviderServiceResource::collection($this->whenLoaded('services')),
            'images'        => ProviderImageResource::collection($this->whenLoaded('images')),
            'reviews'       => ProviderReviewResource::collection($this->whenLoaded('reviews')),
            'created_at'    => $this->created_at?->toDateTimeString(),
        ];
    }
}
