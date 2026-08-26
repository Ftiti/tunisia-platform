<?php
namespace App\Services;

use App\Http\Clients\ProviderServiceClient;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Service de disponibilités du Booking Service.
 *
 * Responsabilités :
 *  - Récupérer les créneaux du planning depuis le Provider Service
 *  - Filtrer les créneaux déjà occupés par des réservations locales
 *  - Vérifier qu'un créneau est disponible avant création d'une réservation
 */
class AvailabilityService
{
    private const CACHE_TTL  = 300; // 5 minutes
    private const SLOT_STEP  = 30;  // pas entre créneaux (minutes)

    public function __construct(
        private readonly BookingRepositoryInterface $bookingRepo,
        private readonly ProviderServiceClient      $providerClient,
    ) {}

    /**
     * Retourne les créneaux disponibles pour un prestataire à une date donnée.
     * = créneaux du planning (Provider Service) MINUS créneaux déjà réservés (local).
     */
    public function getAvailableSlots(int $providerId, string $date, int $durationMinutes = 60): array
    {
        $cacheKey = "availability:{$providerId}:{$date}:{$durationMinutes}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($providerId, $date, $durationMinutes) {
            // 1. Récupérer les créneaux planifiés depuis le Provider Service
            $allSlots = $this->providerClient->getAvailableSlots($providerId, $date, $durationMinutes);

            if (empty($allSlots)) {
                return [];
            }

            // 2. Récupérer les heures de début déjà réservées (local)
            $bookedTimes = $this->getBookedTimes($providerId, $date);

            // 3. Filtrer les créneaux chevauchés par des réservations
            return array_values(
                array_filter($allSlots, function (array $slot) use ($providerId, $date, $bookedTimes, $durationMinutes) {
                    $time    = $slot['time'];
                    $endTime = $slot['end_time'] ?? Carbon::parse($time)->addMinutes($durationMinutes)->format('H:i');

                    // Vérifier aucun chevauchement avec les réservations actives
                    return $this->bookingRepo->getActiveBookingsForSlot(
                        $providerId, $date, $time, $endTime
                    ) === 0;
                })
            );
        });
    }

    /**
     * Vérifie si un créneau précis est disponible avant de créer une réservation.
     *
     * Deux conditions nécessaires :
     *  1. Le prestataire est planifié pour être ouvert (Provider Service)
     *  2. Aucune réservation active ne chevauche ce créneau (local)
     */
    public function isSlotAvailable(
        int    $providerId,
        int    $serviceId,
        string $date,
        string $time
    ): bool {
        // 1. Récupérer les infos du service (durée)
        $service = $this->providerClient->getService($providerId, $serviceId);
        if (!$service) {
            return false;
        }

        $durationMinutes = (int) ($service['duration_minutes'] ?? 60);
        $endTime = Carbon::parse($time)->addMinutes($durationMinutes)->format('H:i');

        // 2. Vérifier le planning du prestataire (Provider Service)
        $scheduledOpen = $this->providerClient->checkAvailability($providerId, $date, $time);
        if (!$scheduledOpen) {
            return false;
        }

        // 3. Vérifier l'absence de conflits locaux
        return $this->bookingRepo->getActiveBookingsForSlot(
            $providerId, $date, $time, $endTime
        ) === 0;
    }

    /**
     * Trouve le prochain jour où le prestataire a au moins un créneau libre.
     */
    public function getNextAvailableDate(int $providerId, int $durationMinutes = 60): ?Carbon
    {
        $date = Carbon::today();

        for ($i = 0; $i < 60; $i++) {
            $slots = $this->getAvailableSlots($providerId, $date->format('Y-m-d'), $durationMinutes);
            if (!empty($slots)) {
                return $date->copy();
            }
            $date->addDay();
        }

        return null;
    }

    /**
     * Invalide le cache des disponibilités pour un prestataire/date.
     * Appelé après création ou annulation d'une réservation.
     */
    public function invalidateCache(int $providerId, string $date): void
    {
        foreach ([30, 60, 90, 120] as $dur) {
            Cache::forget("availability:{$providerId}:{$date}:{$dur}");
        }
    }

    // ── Privé ─────────────────────────────────────────────────────────────────

    /** Retourne les heures de début des réservations actives pour un prestataire/date. */
    private function getBookedTimes(int $providerId, string $date): array
    {
        return \App\Models\Booking::where('provider_id', $providerId)
            ->whereDate('scheduled_date', $date)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->pluck('scheduled_time')
            ->map(fn($t) => substr($t, 0, 5)) // 'H:i:s' → 'H:i'
            ->toArray();
    }
}
