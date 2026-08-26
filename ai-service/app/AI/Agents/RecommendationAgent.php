<?php

namespace App\AI\Agents;

use App\AI\OllamaClient;
use App\Http\Clients\BookingServiceClient;
use App\Http\Clients\ProviderServiceClient;
use App\Models\AiRecommendation;
use Illuminate\Support\Facades\Cache;

class RecommendationAgent
{
    private const CACHE_TTL     = 3600;  // 1 heure
    private const MAX_RESULTS   = 10;

    public function __construct(
        private readonly OllamaClient          $ollama,
        private readonly ProviderServiceClient $providerClient,
        private readonly BookingServiceClient  $bookingClient,
    ) {}

    /**
     * Générer des recommandations personnalisées pour un utilisateur.
     */
    public function recommend(int $userId, array $location = []): array
    {
        $cacheKey = "recommendations:{$userId}";

        // Vérifier le cache Redis d'abord
        if ($cached = Cache::get($cacheKey)) {
            return array_merge($cached, ['from_cache' => true]);
        }

        // STEP 1 — Récupérer l'historique de l'utilisateur
        $history = $this->bookingClient->getUserBookings($userId);

        // STEP 2 — Analyser les préférences via IA
        $preferences = $this->analyzePreferences($history);

        // STEP 3 — Rechercher les prestataires correspondants
        $searchFilters = array_filter(array_merge(
            $preferences['filters'] ?? [],
            $location,
            ['per_page' => 30]
        ), fn($v) => !is_null($v));

        $providers = $this->providerClient->search($searchFilters);

        // STEP 4 — Scorer et trier selon les préférences
        $scored = $this->scoreProviders($providers, $preferences);
        $top    = array_slice($scored, 0, self::MAX_RESULTS);

        // STEP 5 — Persister en base
        $providerIds = array_column($top, 'id');

        AiRecommendation::create([
            'user_id'      => $userId,
            'provider_ids' => $providerIds,
            'reason'       => $preferences['reason'] ?? 'Recommandations personnalisées',
            'expires_at'   => now()->addHour(),
        ]);

        $result = [
            'providers'  => $top,
            'reason'     => $preferences['reason'] ?? 'Prestataires recommandés pour vous',
            'based_on'   => count($history) . ' réservation(s) précédente(s)',
            'from_cache' => false,
        ];

        Cache::put($cacheKey, $result, self::CACHE_TTL);

        return $result;
    }

    /**
     * Analyser l'historique et extraire les préférences utilisateur.
     */
    private function analyzePreferences(array $history): array
    {
        if (empty($history)) {
            return [
                'filters'              => ['sort_by' => 'rating', 'min_rating' => 4],
                'preferred_categories' => [],
                'reason'               => 'Prestataires les mieux notés de la plateforme',
            ];
        }

        $historyText = collect($history)
            ->take(10)
            ->map(function ($b) {
                $service  = $b['service_name']  ?? $b['service']['name']  ?? 'service';
                $provider = $b['provider_name'] ?? $b['provider']['name'] ?? 'prestataire';
                $status   = $b['status'] ?? '';
                return "- {$service} chez {$provider}" . ($status ? " ({$status})" : '');
            })
            ->join("\n");

        $result = $this->ollama->chatJSON(
            config('services.ollama.model_recommend', 'llama3.1:8b'),
            [
                [
                    'role'    => 'user',
                    'content' => <<<PROMPT
                    Analyse cet historique de réservations d'un utilisateur tunisien et retourne:
                    {
                      "preferred_categories": ["cat1", "cat2"],
                      "filters": {"min_rating": 4, "sort_by": "rating"},
                      "reason": "explication courte en français (max 15 mots)"
                    }

                    Historique:
                    {$historyText}
                    PROMPT,
                ],
            ]
        );

        return array_merge([
            'preferred_categories' => [],
            'filters'              => ['sort_by' => 'rating'],
            'reason'               => 'Basé sur votre historique',
        ], $result);
    }

    /**
     * Scorer les prestataires selon les préférences.
     */
    private function scoreProviders(array $providers, array $preferences): array
    {
        $preferredCategories = array_map(
            'strtolower',
            $preferences['preferred_categories'] ?? []
        );

        $scored = array_map(function ($provider) use ($preferredCategories) {
            $score = (float) ($provider['rating'] ?? 0);

            // Bonus si catégorie préférée
            $categoryName = strtolower($provider['category']['name'] ?? '');
            foreach ($preferredCategories as $pref) {
                if (str_contains($categoryName, $pref) || str_contains($pref, $categoryName)) {
                    $score += 2.0;
                    break;
                }
            }

            // Bonus si vérifié
            if ($provider['is_verified'] ?? false) {
                $score += 0.5;
            }

            // Bonus si mis en avant
            if ($provider['is_featured'] ?? false) {
                $score += 0.3;
            }

            return array_merge($provider, ['_score' => round($score, 2)]);
        }, $providers);

        usort($scored, fn($a, $b) => $b['_score'] <=> $a['_score']);

        return $scored;
    }
}
