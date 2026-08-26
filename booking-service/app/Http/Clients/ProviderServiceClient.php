<?php
namespace App\Http\Clients;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client HTTP vers le Provider Service (port 8002).
 *
 * Toutes les méthodes retournent null / [] / false en cas d'erreur réseau
 * pour ne pas bloquer le Booking Service si le Provider Service est indisponible.
 */
class ProviderServiceClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            config('services.provider.url', 'http://tunisia_provider:8002/api/v1'),
            '/'
        );
    }

    // ── Prestataires ──────────────────────────────────────────────────────────

    /**
     * Récupère les données d'un prestataire depuis le Provider Service.
     * Retourne null si introuvable ou si le service est indisponible.
     */
    public function getProvider(int $id): ?array
    {
        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get("{$this->baseUrl}/providers/{$id}");

            return $response->successful()
                ? $response->json('data')
                : null;
        } catch (\Exception $e) {
            Log::warning('[ProviderServiceClient] getProvider failed', [
                'provider_id' => $id,
                'error'       => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ── Services ──────────────────────────────────────────────────────────────

    /**
     * Récupère un service spécifique d'un prestataire.
     */
    public function getService(int $providerId, int $serviceId): ?array
    {
        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get("{$this->baseUrl}/providers/{$providerId}/services/{$serviceId}");

            return $response->successful()
                ? $response->json('data')
                : null;
        } catch (\Exception $e) {
            Log::warning('[ProviderServiceClient] getService failed', [
                'provider_id' => $providerId,
                'service_id'  => $serviceId,
                'error'       => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Récupère tous les services actifs d'un prestataire.
     */
    public function getProviderServices(int $providerId): array
    {
        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get("{$this->baseUrl}/providers/{$providerId}/services");

            return $response->successful()
                ? ($response->json('data') ?? [])
                : [];
        } catch (\Exception $e) {
            Log::warning('[ProviderServiceClient] getProviderServices failed', [
                'provider_id' => $providerId,
                'error'       => $e->getMessage(),
            ]);
            return [];
        }
    }

    // ── Disponibilités ────────────────────────────────────────────────────────

    /**
     * Vérifie si un prestataire est planifié pour être ouvert à une date/heure.
     * (basé sur son planning — sans tenir compte des réservations existantes)
     */
    public function checkAvailability(int $providerId, string $date, string $time): bool
    {
        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get("{$this->baseUrl}/providers/{$providerId}/availability", [
                    'date' => $date,
                    'time' => $time,
                ]);

            return (bool) $response->json('data.available', false);
        } catch (\Exception $e) {
            Log::warning('[ProviderServiceClient] checkAvailability failed', [
                'provider_id' => $providerId,
                'date'        => $date,
                'time'        => $time,
                'error'       => $e->getMessage(),
            ]);
            // En cas de timeout, on autorise (le check de conflits local reste actif)
            return true;
        }
    }

    /**
     * Récupère les créneaux du planning pour un prestataire à une date donnée.
     * Chaque élément : ['time' => 'H:i', 'end_time' => 'H:i']
     */
    public function getAvailableSlots(
        int    $providerId,
        string $date,
        int    $durationMinutes = 60,
        ?int   $serviceId       = null
    ): array {
        try {
            $params = ['duration_minutes' => $durationMinutes];
            if ($serviceId) $params['service_id'] = $serviceId;

            $response = Http::timeout(5)
                ->acceptJson()
                ->get("{$this->baseUrl}/availability/{$providerId}/{$date}", $params);

            return $response->successful()
                ? ($response->json('data.slots') ?? [])
                : [];
        } catch (\Exception $e) {
            Log::warning('[ProviderServiceClient] getAvailableSlots failed', [
                'provider_id' => $providerId,
                'date'        => $date,
                'error'       => $e->getMessage(),
            ]);
            return [];
        }
    }
}
