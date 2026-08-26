<?php

namespace App\Http\Clients;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BookingServiceClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            config('services.booking.url', 'http://tunisia_booking:8001/api'),
            '/'
        );
    }

    /**
     * Récupérer les réservations d'un utilisateur.
     *
     * @return array Liste des réservations
     */
    public function getUserBookings(int $userId): array
    {
        try {
            $response = Http::timeout(10)
                ->get("{$this->baseUrl}/bookings", [
                    'user_id'  => $userId,
                    'per_page' => 20,
                ]);

            if ($response->failed()) {
                Log::warning('BookingServiceClient::getUserBookings failed', [
                    'user_id' => $userId,
                    'status'  => $response->status(),
                ]);
                return [];
            }

            return $response->json('data.bookings') ?? [];

        } catch (\Throwable $e) {
            Log::error('BookingServiceClient::getUserBookings error', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Récupérer une réservation par ID.
     */
    public function getBooking(int $id): ?array
    {
        try {
            $response = Http::timeout(10)
                ->get("{$this->baseUrl}/bookings/{$id}");

            if ($response->failed()) {
                return null;
            }

            return $response->json('data') ?? null;

        } catch (\Throwable $e) {
            Log::error('BookingServiceClient::getBooking error', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
