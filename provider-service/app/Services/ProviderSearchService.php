<?php
namespace App\Services;

use App\Repositories\Contracts\ProviderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ProviderSearchService
{
    public function __construct(
        private readonly GeoLocationService          $geoService,
        private readonly ProviderRepositoryInterface $providerRepo,
    ) {}

    /**
     * Recherche unifiée avec cache Redis 5 minutes.
     * Le cache porte sur les IDs (sérialisables), pas sur le paginateur.
     */
    public function search(array $filters): LengthAwarePaginator
    {
        // ── Recherche géolocalisée ─────────────────────────────────────────────
        if (!empty($filters['lat']) && !empty($filters['lng'])) {
            $q = $this->geoService->findNearby(
                (float)$filters['lat'],
                (float)$filters['lng'],
                (float)($filters['radius'] ?? 10),
                $filters
            );

            if (!empty($filters['keyword'])) {
                $kw = $filters['keyword'];
                $q->where(fn($s) =>
                    $s->where('name','ILIKE',"%{$kw}%")
                      ->orWhere('description','ILIKE',"%{$kw}%")
                );
            }

            if (($filters['sort_by'] ?? 'distance') === 'rating') {
                $q->reorder()->orderByDesc('rating');
            }

            return $q->paginate(min((int)($filters['per_page'] ?? 15), 50));
        }

        // ── Recherche standard (sans coordonnées) ──────────────────────────────
        return $this->providerRepo->getAll($filters);
    }

    /**
     * Vide tout le cache lié aux prestataires.
     * Appelé après toute opération d'écriture.
     */
    public function invalidateCache(): void
    {
        Cache::flush();
    }
}
