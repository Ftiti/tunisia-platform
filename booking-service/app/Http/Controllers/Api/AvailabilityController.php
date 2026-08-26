<?php
namespace App\Http\Controllers\Api;

use App\Http\Clients\ProviderServiceClient;
use App\Http\Controllers\Controller;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(
        private readonly AvailabilityService   $availabilityService,
        private readonly ProviderServiceClient $providerClient,
    ) {}

    /**
     * GET /api/availability/{provider_id}/{date}
     *
     * Retourne les créneaux disponibles :
     *   = créneaux du planning (Provider Service) − réservations actives (local)
     *
     * Query params:
     *   - service_id       (int, optionnel) : récupère la durée automatiquement
     *   - duration_minutes (int, défaut 60) : durée souhaitée si pas de service_id
     */
    public function slots(Request $request, int $providerId, string $date): JsonResponse
    {
        $request->validate([
            'service_id'       => 'nullable|integer',
            'duration_minutes' => 'nullable|integer|min:15|max:480',
        ]);

        // Déterminer la durée
        $durationMinutes = (int) ($request->query('duration_minutes', 60));
        if ($request->filled('service_id')) {
            $service = $this->providerClient->getService($providerId, (int) $request->service_id);
            if ($service) {
                $durationMinutes = (int) ($service['duration_minutes'] ?? $durationMinutes);
            }
        }

        // AvailabilityService orchestre tout : Provider Service + filtre local
        $slots = $this->availabilityService->getAvailableSlots($providerId, $date, $durationMinutes);

        return response()->json([
            'success' => true,
            'message' => 'Créneaux disponibles récupérés.',
            'data'    => [
                'provider_id'      => $providerId,
                'date'             => $date,
                'duration_minutes' => $durationMinutes,
                'slots'            => $slots,
                'total_slots'      => count($slots),
            ],
        ]);
    }

    /**
     * GET /api/availability/{provider_id}/next
     *
     * Retourne le prochain jour avec au moins un créneau libre.
     */
    public function next(Request $request, int $providerId): JsonResponse
    {
        $durationMinutes = 60;
        if ($request->filled('service_id')) {
            $service = $this->providerClient->getService($providerId, (int) $request->service_id);
            if ($service) {
                $durationMinutes = (int) ($service['duration_minutes'] ?? 60);
            }
        }

        $date = $this->availabilityService->getNextAvailableDate($providerId, $durationMinutes);

        return response()->json([
            'success' => true,
            'message' => $date
                ? 'Prochain créneau trouvé.'
                : 'Aucun créneau disponible dans les 60 prochains jours.',
            'data'    => [
                'provider_id'    => $providerId,
                'next_available' => $date?->format('Y-m-d'),
            ],
        ]);
    }
}
