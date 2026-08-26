<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'description'        => $this->description,
            'price'              => $this->price,
            'price_max'          => $this->price_max,
            'formatted_price'    => $this->formatted_price,
            'duration_minutes'   => $this->duration_minutes,
            'formatted_duration' => $this->formatted_duration,
            'unit'               => $this->unit,
            'is_active'          => $this->is_active,
        ];
    }
}
