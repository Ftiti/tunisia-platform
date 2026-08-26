<?php
namespace App\Http\Controllers\Api;

use App\Http\Clients\ProviderServiceClient;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService             $bookingService,
        private readonly BookingRepositoryInterface $bookingRepo,
        private readonly ProviderServiceClient      $providerClient,
    ) {}

    /**
     * GET /api/bookings — Liste avec filtres
     */
    public function index(Request $request): JsonResponse
    {
        $user    = $request->user();
        $isAdmin = in_array($user->role ?? '', ['admin', 'super_admin']);

        $filters = $request->only(['status', 'date', 'provider_id', 'user_id', 'per_page']);
        if (!$isAdmin) {
            $filters['user_id'] = $user->id;
        }

        $paginated = $this->bookingRepo->getByFilters($filters);

        return response()->json([
            'success' => true,
            'message' => 'Réservations récupérées.',
            'data'    => [
                'bookings'     => BookingResource::collection($paginated->items()),
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/bookings — Créer une réservation
     *
     * Le provider_id et service_id sont validés en temps réel
     * contre le Provider Service (plus de `exists:providers,id`).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider_id'    => ['required', 'integer'],
            'service_id'     => ['required', 'integer'],
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $booking = $this->bookingService->createBooking(
                array_merge($validated, ['user_id' => $request->user()->id])
            );

            return response()->json([
                'success' => true,
                'message' => 'Réservation créée avec succès.',
                'data'    => new BookingResource($booking),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => $e->errors(),
            ], 422);
        }
    }

    /**
     * GET /api/bookings/{id}
     */
    public function show(int $id): JsonResponse
    {
        $booking = $this->bookingRepo->findById($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Réservation introuvable.',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Réservation récupérée.',
            'data'    => new BookingResource($booking),
        ]);
    }

    /**
     * PUT /api/bookings/{id}/confirm
     */
    public function confirm(int $id): JsonResponse
    {
        try {
            $booking = $this->bookingService->confirmBooking($id);
            return response()->json([
                'success' => true,
                'message' => 'Réservation confirmée.',
                'data'    => new BookingResource($booking),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => $e->errors(),
            ], 422);
        }
    }

    /**
     * PUT /api/bookings/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        try {
            $booking = $this->bookingService->cancelBooking(
                $id,
                $request->input('reason', ''),
                $request->user()->id
            );
            return response()->json([
                'success' => true,
                'message' => 'Réservation annulée.',
                'data'    => new BookingResource($booking),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => $e->errors(),
            ], 422);
        }
    }

    /**
     * PUT /api/bookings/{id}/complete
     */
    public function complete(int $id): JsonResponse
    {
        try {
            $booking = $this->bookingService->completeBooking($id);
            return response()->json([
                'success' => true,
                'message' => 'Réservation marquée comme terminée.',
                'data'    => new BookingResource($booking),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => $e->errors(),
            ], 422);
        }
    }

    /**
     * GET /api/admin/bookings — Liste admin avec filtres
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = Booking::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('provider_id')) {
            $query->where('provider_id', $request->provider_id);
        }

        $bookings = $query->latest()->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => [
                'bookings'     => BookingResource::collection($bookings->items()),
                'total'        => $bookings->total(),
                'current_page' => $bookings->currentPage(),
                'last_page'    => $bookings->lastPage(),
            ],
        ]);
    }
}
