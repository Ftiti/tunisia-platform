<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProviderResource;
use App\Http\Resources\ProviderServiceResource;
use App\Http\Resources\ProviderReviewResource;
use App\Models\Provider;
use App\Models\ProviderReview;
use App\Models\ProviderService;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use App\Services\GeoLocationService;
use App\Services\ProviderAvailabilityService;
use App\Services\ProviderSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProviderController extends Controller
{
    public function __construct(
        private readonly ProviderRepositoryInterface $repo,
        private readonly GeoLocationService          $geoService,
        private readonly ProviderSearchService       $searchService,
        private readonly ProviderAvailabilityService $availabilityService,
    ) {}

    /** GET /api/v1/providers */
    public function index(Request $request): JsonResponse
    {
        $filters   = $request->only(['lat','lng','radius','category_id','city','governorate',
                                     'keyword','sort_by','per_page','is_verified','is_featured','min_rating','is_open']);
        $paginated = $this->searchService->search($filters);

        return response()->json([
            'success' => true,
            'message' => 'Prestataires récupérés.',
            'data'    => [
                'providers'    => ProviderResource::collection($paginated->items()),
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
            ],
        ]);
    }

    /** GET /api/v1/providers/nearby */
    public function nearby(Request $request): JsonResponse
    {
        $request->validate([
            'lat'         => 'required|numeric|between:-90,90',
            'lng'         => 'required|numeric|between:-180,180',
            'radius'      => 'nullable|numeric|min:1|max:50',
            'category_id' => 'nullable|integer|exists:provider_categories,id',
        ]);

        $paginated = $this->geoService->findNearby(
            (float)$request->lat,
            (float)$request->lng,
            (float)($request->radius ?? 5),
            $request->only(['category_id','is_open','is_verified','min_rating'])
        )->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Prestataires proches récupérés.',
            'data'    => [
                'providers'    => ProviderResource::collection($paginated->items()),
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
            ],
        ]);
    }

    /** GET /api/v1/providers/{id} */
    public function show(int $id): JsonResponse
    {
        $provider = $this->repo->findById($id);
        if (!$provider) {
            return response()->json(['success' => false, 'message' => 'Prestataire introuvable.', 'data' => null], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Prestataire récupéré.',
            'data'    => array_merge(
                (new ProviderResource($provider))->toArray(request()),
                [
                    'open_status'     => $this->availabilityService->getOpenStatus($provider),
                    'weekly_schedule' => $this->availabilityService->getWeeklySchedule($provider),
                ]
            ),
        ]);
    }

    /** POST /api/v1/providers */
    public function store(Request $request): JsonResponse
    {
        $v = $request->validate([
            'name'        => 'required|string|max:255',
            'category_id' => 'required|integer|exists:provider_categories,id',
            'address'     => 'required|string|max:500',
            'city'        => 'required|string|max:100',
            'governorate' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'phone'       => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:255',
            'website'     => 'nullable|url|max:255',
            'description' => 'nullable|string|max:2000',
            'lat'         => 'nullable|numeric|between:-90,90',
            'lng'         => 'nullable|numeric|between:-180,180',
        ]);

        $v['slug'] = $this->uniqueSlug($v['name']);
        $provider  = $this->repo->create($v);
        if (($v['lat'] ?? null) !== null && ($v['lng'] ?? null) !== null) {
            $this->geoService->saveLocation($provider, $v['lat'], $v['lng']);
        }
        $this->searchService->invalidateCache();

        return response()->json([
            'success' => true,
            'message' => 'Prestataire créé.',
            'data'    => new ProviderResource($provider->fresh(['category','services','schedules'])),
        ], 201);
    }

    /** PUT /api/v1/providers/{id} */
    public function update(Request $request, int $id): JsonResponse
    {
        $provider = $this->repo->findById($id);
        if (!$provider) {
            return response()->json(['success' => false, 'message' => 'Prestataire introuvable.', 'data' => null], 404);
        }

        $v = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'category_id' => 'sometimes|integer|exists:provider_categories,id',
            'address'     => 'sometimes|string|max:500',
            'city'        => 'sometimes|string|max:100',
            'governorate' => 'sometimes|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'phone'       => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:255',
            'website'     => 'nullable|url|max:255',
            'description' => 'nullable|string|max:2000',
            'is_active'   => 'sometimes|boolean',
            'is_verified' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'lat'         => 'nullable|numeric|between:-90,90',
            'lng'         => 'nullable|numeric|between:-180,180',
        ]);

        if (isset($v['name'])) $v['slug'] = $this->uniqueSlug($v['name'], $id);

        $lat = $v['lat'] ?? null;
        $lng = $v['lng'] ?? null;
        unset($v['lat'], $v['lng']);

        $provider = $this->repo->update($provider, $v);
        if ($lat !== null && $lng !== null) {
            $this->geoService->saveLocation($provider, $lat, $lng);
        }
        $this->searchService->invalidateCache();

        return response()->json([
            'success' => true,
            'message' => 'Prestataire mis à jour.',
            'data'    => new ProviderResource($provider->fresh(['category','services','schedules'])),
        ]);
    }

    /** DELETE /api/v1/providers/{id} */
    public function destroy(int $id): JsonResponse
    {
        $provider = $this->repo->findById($id);
        if (!$provider) {
            return response()->json(['success' => false, 'message' => 'Prestataire introuvable.', 'data' => null], 404);
        }
        $this->repo->delete($provider);
        $this->searchService->invalidateCache();
        return response()->json(['success' => true, 'message' => 'Prestataire supprimé.', 'data' => null]);
    }

    /** GET /api/v1/providers/{id}/services */
    public function services(int $id): JsonResponse
    {
        $provider = Provider::findOrFail($id);
        return response()->json([
            'success' => true, 'message' => 'Services récupérés.',
            'data'    => ProviderServiceResource::collection($provider->services()->get()),
        ]);
    }

    /** GET /api/v1/providers/{providerId}/services/{serviceId} */
    public function serviceById(int $providerId, int $serviceId): JsonResponse
    {
        $service = ProviderService::where('id', $serviceId)
            ->where('provider_id', $providerId)
            ->where('is_active', true)
            ->first();

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Service introuvable.',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Service récupéré.',
            'data'    => new ProviderServiceResource($service),
        ]);
    }

    /** POST /api/v1/providers/{id}/services */
    public function storeService(Request $request, int $id): JsonResponse
    {
        $provider = Provider::findOrFail($id);
        $v = $request->validate([
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string|max:1000',
            'price'            => 'required|numeric|min:0',
            'price_max'        => 'nullable|numeric|min:0',
            'duration_minutes' => 'required|integer|min:1',
            'unit'             => 'nullable|in:fixed,hour,day',
            'is_active'        => 'boolean',
        ]);
        $v['unit']      = $v['unit']      ?? 'fixed';
        $v['is_active'] = $v['is_active'] ?? true;
        $service = $provider->services()->create($v);
        return response()->json([
            'success' => true, 'message' => 'Service créé.',
            'data'    => new ProviderServiceResource($service),
        ], 201);
    }

    /** PUT /api/v1/providers/{id}/services/{serviceId} */
    public function updateService(Request $request, int $id, int $serviceId): JsonResponse
    {
        $service = ProviderService::where('provider_id', $id)->findOrFail($serviceId);
        $v = $request->validate([
            'name'             => 'sometimes|string|max:255',
            'description'      => 'nullable|string|max:1000',
            'price'            => 'sometimes|numeric|min:0',
            'price_max'        => 'nullable|numeric|min:0',
            'duration_minutes' => 'sometimes|integer|min:1',
            'unit'             => 'nullable|in:fixed,hour,day',
            'is_active'        => 'sometimes|boolean',
        ]);
        // Ne pas écraser unit par null si non fourni
        if (array_key_exists('unit', $v) && $v['unit'] === null) {
            $v['unit'] = 'fixed';
        }
        $service->update($v);
        return response()->json([
            'success' => true, 'message' => 'Service mis à jour.',
            'data'    => new ProviderServiceResource($service->fresh()),
        ]);
    }

    /** DELETE /api/v1/providers/{id}/services/{serviceId} */
    public function destroyService(int $id, int $serviceId): JsonResponse
    {
        $service = ProviderService::where('provider_id', $id)->findOrFail($serviceId);
        $service->delete();
        return response()->json(['success' => true, 'message' => 'Service supprimé.', 'data' => null]);
    }

    /** GET /api/v1/providers/{id}/reviews */
    public function reviews(int $id): JsonResponse
    {
        $provider  = Provider::findOrFail($id);
        $paginated = $provider->reviews()->paginate(20);
        return response()->json([
            'success' => true, 'message' => 'Avis récupérés.',
            'data'    => [
                'reviews'      => ProviderReviewResource::collection($paginated->items()),
                'total'        => $paginated->total(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
            ],
        ]);
    }

    /** GET /api/v1/providers/{id}/schedule */
    public function schedule(int $id): JsonResponse
    {
        $provider = Provider::findOrFail($id);
        return response()->json([
            'success' => true, 'message' => 'Horaires récupérés.',
            'data'    => [
                'open_status' => $this->availabilityService->getOpenStatus($provider),
                'schedule'    => $this->availabilityService->getWeeklySchedule($provider),
            ],
        ]);
    }

    /** PUT /api/v1/providers/{id}/schedule */
    public function updateSchedule(Request $request, int $id): JsonResponse
    {
        $provider = Provider::findOrFail($id);

        // Accepte HH:MM (input HTML) et HH:MM:SS (retour PostgreSQL TIME)
        $timeRule = ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'];

        $validated = $request->validate([
            'schedules'               => 'required|array|min:7|max:7',
            'schedules.*.day'         => 'required|integer|between:0,6',
            'schedules.*.is_closed'   => 'required|boolean',
            'schedules.*.open_time'   => $timeRule,
            'schedules.*.close_time'  => $timeRule,
            'schedules.*.break_start' => $timeRule,
            'schedules.*.break_end'   => $timeRule,
        ]);

        foreach ($validated['schedules'] as $item) {
            $isClosed = (bool) $item['is_closed'];
            // Normalise "HH:MM:SS" → "HH:MM" pour un stockage cohérent
            $t = fn(?string $v) => $v ? substr($v, 0, 5) : null;
            $provider->schedules()->updateOrCreate(
                ['day_of_week' => $item['day']],
                [
                    'is_closed'   => $isClosed,
                    'open_time'   => $isClosed ? null : $t($item['open_time']  ?? null),
                    'close_time'  => $isClosed ? null : $t($item['close_time'] ?? null),
                    'break_start' => $isClosed ? null : $t($item['break_start'] ?? null),
                    'break_end'   => $isClosed ? null : $t($item['break_end']   ?? null),
                ]
            );
        }

        $provider->refresh();
        return response()->json([
            'success' => true,
            'message' => 'Horaires mis à jour.',
            'data'    => $this->availabilityService->getWeeklySchedule($provider),
        ]);
    }

    /** POST /api/v1/providers/{id}/reviews */
    public function addReview(Request $request, int $id): JsonResponse
    {
        $provider = Provider::findOrFail($id);
        $v = $request->validate([
            'rating'     => 'required|integer|min:1|max:5',
            'comment'    => 'nullable|string|max:1000',
            'booking_id' => 'nullable|integer',
        ]);

        $review = ProviderReview::create([...$v, 'provider_id' => $provider->id, 'user_id' => $request->user()->id]);
        $provider->updateRating();

        return response()->json([
            'success' => true, 'message' => 'Avis ajouté.',
            'data'    => new ProviderReviewResource($review),
        ], 201);
    }

    private function uniqueSlug(string $name, ?int $excludeId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 1;
        while (Provider::where('slug', $slug)->when($excludeId, fn($q) => $q->where('id','!=',$excludeId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }
}
