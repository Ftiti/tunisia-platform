<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AvailabilityResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request): array
    {
        return [
            'time'     => $this['time'],
            'end_time' => $this['end_time'],
        ];
    }
}
