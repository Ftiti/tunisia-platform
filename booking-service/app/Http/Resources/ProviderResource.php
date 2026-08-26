<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProviderResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'user_id'       => $this->user_id,
            'name'          => $this->name,
            'category'      => $this->whenLoaded('category', fn() => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'description'   => $this->description,
            'address'       => $this->address,
            'city'          => $this->city,
            'phone'         => $this->phone,
            'email'         => $this->email,
            'rating'        => $this->rating,
            'total_reviews' => $this->total_reviews,
            'is_active'     => $this->is_active,
            'is_verified'   => $this->is_verified,
            'services'      => ServiceResource::collection($this->whenLoaded('services')),
            'schedules'     => $this->whenLoaded('schedules'),
            'distance'      => isset($this->distance) ? round($this->distance / 1000, 2) . ' km' : null,
        ];
    }
}
