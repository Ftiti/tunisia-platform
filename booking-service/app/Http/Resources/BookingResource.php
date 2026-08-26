<?php
namespace App\Http\Resources;

use App\Http\Clients\ProviderServiceClient;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request): array
    {
        // Enrichissement lazy depuis le Provider Service
        // On ne fait les appels HTTP que si les données sont nécessaires
        $client   = app(ProviderServiceClient::class);
        $provider = $client->getProvider($this->provider_id);
        $service  = $client->getService($this->provider_id, $this->service_id);

        return [
            'id'                  => $this->id,
            'user_id'             => $this->user_id,
            'provider_id'         => $this->provider_id,
            'service_id'          => $this->service_id,
            // Données enrichies depuis le Provider Service
            'provider'            => $provider,
            'service'             => $service,
            // Données de réservation (locales)
            'scheduled_date'      => $this->scheduled_date?->format('Y-m-d'),
            'scheduled_time'      => substr($this->scheduled_time ?? '', 0, 5),
            'end_time'            => substr($this->end_time ?? '', 0, 5),
            'status'              => $this->status,
            'total_price'         => $this->total_price,
            'notes'               => $this->notes,
            'cancellation_reason' => $this->cancellation_reason,
            'confirmed_at'        => $this->confirmed_at?->toISOString(),
            'cancelled_at'        => $this->cancelled_at?->toISOString(),
            'completed_at'        => $this->completed_at?->toISOString(),
            'created_at'          => $this->created_at->toISOString(),
        ];
    }
}
