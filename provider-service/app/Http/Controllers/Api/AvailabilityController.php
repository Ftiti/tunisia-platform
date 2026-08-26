<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\ProviderService;
use App\Services\ProviderAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(
        private readonly ProviderAvailabilityService $availabilityService
    ) {}

    /**
     * GET /api/v1/availability/{providerId}/{date}
     *
     * Retourne les créneaux du planning pour un prestataire à une date donnée.
     * Appelé par le Booking Service, qui filtre ensuite les créneaux déjà réservés.
     *
     * Query params:
     *   - duration_minutes (int, defaut 60) : durée du service recherché
     *   - service_id (int, optionnel)        : récupère la durée automatiquement
     */
    public function slots(Request $request, int $providerId, string $date): JsonResponse
    {
        $provider = Provider::with('schedules')->find($providerId);

        if (!$provider || !$provider->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Prestataire introuvable ou inactif.',
                'data'    => null,
            ], 404);
        }

        // Résoudre la durée du service
        $durationMinutes = (int)($request->query('duration_minutes', 60));
        if ($request->filled('service_id')) {
            $service = ProviderService::where('id', $request->service_id)
                ->where('provider_id', $providerId)
                ->where('is_active', true)
                ->first();
            if ($service) {
                $durationMinutes = $service->duration_minutes;
            }
        }

        $slots = $this->availabilityService->getSlotsForDate($provider, $date, $durationMinutes);

        return response()->json([
            'success' => true,
            'message' => 'Créneaux récupérés.',
            'data'    => [
                'provider_id'      => $providerId,
                'date'             => $date,
                'duration_minutes' => $durationMinutes,
                'slots'            => $slots,
            ],
        ]);
    }

    /**
     * GET /api/v1/providers/{providerId}/availability
     *
     * Vérifie si un créneau précis est dans le planning du prestataire.
     * Le Booking Service l'appelle pour valider avant de créer une réservation.
     *
     * Query params: date (Y-m-d), time (H:i)
     */
    public function check(Request $request, int $providerId): JsonResponse
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|date_format:H:i',
        ]);

        $provider = Provider::with('schedules')->find($providerId);

        if (!$provider || !$provider->is_active) {
            return response()->json([
                'success' => true,
                'data'    => ['available' => false, 'reason' => 'Prestataire introuvable.'],
            ]);
        }

        $available = $this->availabilityService->isScheduledOpen(
            $provider,
            $request->date,
            $request->time
        );

        return response()->json([
            'success' => true,
            'message' => $available ? 'Créneau disponible.' : 'Créneau non disponible.',
            'data'    => [
                'available'   => $available,
                'provider_id' => $providerId,
                'date'        => $request->date,
                'time'        => $request->time,
            ],
        ]);
    }
}
