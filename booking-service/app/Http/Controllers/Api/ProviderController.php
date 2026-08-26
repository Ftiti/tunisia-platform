<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProviderResource;
use App\Models\Schedule;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use App\Http\Requests\UpdateScheduleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function __construct(private readonly ProviderRepositoryInterface $providerRepo) {}

    /** GET /api/providers */
    public function index(Request $request): JsonResponse
    {
        $providers = $this->providerRepo->getByFilters($request->only([
            'category_id', 'city', 'search', 'lat', 'lng',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Prestataires récupérés.',
            'data'    => [
                'providers'    => ProviderResource::collection($providers->items()),
                'total'        => $providers->total(),
                'per_page'     => $providers->perPage(),
                'current_page' => $providers->currentPage(),
                'last_page'    => $providers->lastPage(),
            ],
        ]);
    }

    /** POST /api/providers */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
            'description' => 'nullable|string',
            'address'     => 'required|string|max:255',
            'city'        => 'required|string|max:100',
            'phone'       => 'nullable|string|max:20',
            'email'       => 'nullable|email',
            'lat'         => 'nullable|numeric|between:-90,90',
            'lng'         => 'nullable|numeric|between:-180,180',
        ]);

        $data['user_id'] = $request->user()->id;
        $provider = $this->providerRepo->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Prestataire créé avec succès.',
            'data'    => new ProviderResource($provider),
        ], 201);
    }

    /** GET /api/providers/{id} */
    public function show(int $id): JsonResponse
    {
        $provider = $this->providerRepo->findById($id);

        if (! $provider) {
            return response()->json(['success' => false, 'message' => 'Prestataire introuvable.', 'data' => null], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Prestataire récupéré.',
            'data'    => new ProviderResource($provider),
        ]);
    }

    /** PUT /api/providers/{id} */
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'category_id' => 'sometimes|integer|exists:categories,id',
            'description' => 'nullable|string',
            'address'     => 'sometimes|string|max:255',
            'city'        => 'sometimes|string|max:100',
            'phone'       => 'nullable|string|max:20',
            'email'       => 'nullable|email',
            'lat'         => 'nullable|numeric|between:-90,90',
            'lng'         => 'nullable|numeric|between:-180,180',
            'is_active'   => 'sometimes|boolean',
        ]);

        $provider = $this->providerRepo->update($id, $data);

        return response()->json([
            'success' => true,
            'message' => 'Prestataire mis à jour.',
            'data'    => new ProviderResource($provider),
        ]);
    }

    /** GET /api/providers/{id}/schedule */
    public function schedule(int $id): JsonResponse
    {
        $schedules = Schedule::where('provider_id', $id)->get();

        return response()->json([
            'success' => true,
            'message' => 'Horaires récupérés.',
            'data'    => $schedules,
        ]);
    }

    /** PUT /api/providers/{id}/schedule */
    public function updateSchedule(UpdateScheduleRequest $request, int $id): JsonResponse
    {
        foreach ($request->validated()['schedules'] as $s) {
            Schedule::updateOrCreate(
                ['provider_id' => $id, 'day_of_week' => $s['day_of_week']],
                [
                    'is_closed'  => $s['is_closed'],
                    'open_time'  => $s['is_closed'] ? null : $s['open_time'],
                    'close_time' => $s['is_closed'] ? null : $s['close_time'],
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Horaires mis à jour.',
            'data'    => Schedule::where('provider_id', $id)->get(),
        ]);
    }
}
