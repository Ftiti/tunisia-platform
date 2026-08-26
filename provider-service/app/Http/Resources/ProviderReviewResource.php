<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'user_id'     => $this->user_id,
            'booking_id'  => $this->booking_id,
            'rating'      => $this->rating,
            'comment'     => $this->comment,
            'is_verified' => $this->is_verified,
            'created_at'  => $this->created_at?->toDateTimeString(),
        ];
    }
}
