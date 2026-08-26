<?php

namespace App\AI\Agents;

use App\AI\OllamaClient;
use App\Http\Clients\ProviderServiceClient;
use App\Models\AiSearchLog;

class SearchAgent
{
    public function __construct(
        private readonly OllamaClient          $ollama,
        private readonly ProviderServiceClient $providerClient,
    ) {}


    public function parseQuery(string $userQuery): array
    {
        $result = $this->ollama->chatJSON(
            config('services.ollama.model_classify', 'mistral:7b'),
            [
                [
                    'role'    => 'user',
                    'content' => <<<PROMPT
                    Analyse cette recherche de service en Tunisie et retourne UNIQUEMENT ce JSON (aucun texte avant ou après) :
                    {
                      "category": "type de service (restaurant, plombier, médecin...) ou null",
                      "subcategory": "sous-catégorie spécifique si mentionnée ou null",
                      "city": "ville tunisienne EXPLICITEMENT mentionnée dans la recherche ou null (ne pas deviner)",
                      "is_open": true si "ouvert maintenant/urgent" est mentionné sinon null,
                      "max_price": nombre si prix mentionné sinon null,
                      "min_rating": nombre entre 1 et 5 si note mentionnée sinon null,
                      "keywords": ["mots-clés supplémentaires uniquement, pas la catégorie ni la ville"],
                      "intent": "search" ou "emergency" ou "appointment",
                      "search_mode": "service" si recherche de service, "product" si recherche de produit, "provider" par défaut
                    }

                    RÈGLE IMPORTANTE: city doit être null si aucune ville n'est mentionnée dans la recherche.

                    Recherche: {$userQuery}
                    PROMPT,
                ],
            ]
        );

        return array_merge([
            'category'    => null,
            'subcategory' => null,
            'city'        => null,
            'is_open'     => null,
            'max_price'   => null,
            'min_rating'  => null,
            'keywords'    => [],
            'intent'      => 'search',
            'search_mode' => 'provider',
        ], $result);
    }

    /**
     * STEP 2 — Rechercher les prestataires via ProviderService.
     */
    public function search(string $userQuery, array $location = []): array
    {
        $startTime = now();

        $filters = $this->parseQuery($userQuery);

        // Résoudre le nom de catégorie → category_id via le Provider Service
        $categoryId = null;
        $categories = [];
        if (!empty($filters['category'])) {
            $categories = $this->providerClient->getCategories();
            foreach ($categories as $cat) {
                if (stripos($cat['name'] ?? '', $filters['category']) !== false ||
                    stripos($filters['category'], $cat['name'] ?? '') !== false) {
                    $categoryId = $cat['id'];
                    break;
                }
            }
        }

        // Résoudre la sous-catégorie si présente
        $subcategoryId = null;
        if (!empty($filters['subcategory'])) {
            // Reuse already fetched categories if available, otherwise fetch
            if (empty($categories)) {
                $categories = $this->providerClient->getCategories();
            }
            foreach ($categories as $cat) {
                if (stripos($cat['name'] ?? '', $filters['subcategory']) !== false ||
                    stripos($filters['subcategory'], $cat['name'] ?? '') !== false) {
                    $subcategoryId = $cat['id'];
                    break;
                }
            }
        }

        // Nettoyer les keywords :
        // - retirer la ville (déjà dans le filtre city)
        // - retirer le nom de catégorie si category_id résolu (doublon inutile)
        $city         = strtolower(trim($filters['city'] ?? ''));
        $categoryName = strtolower(trim($filters['category'] ?? ''));
        $keywords     = array_filter(
            $filters['keywords'] ?? [],
            function ($k) use ($city, $categoryName, $categoryId) {
                $kLower = strtolower(trim($k));
                if ($kLower === $city) return false;
                // Si la catégorie est résolue en ID, pas besoin du mot dans le keyword
                if ($categoryId && ($kLower === $categoryName || stripos($categoryName, $kLower) !== false)) {
                    return false;
                }
                return true;
            }
        );
        // Si category_id est résolu, on n'envoie pas de keyword :
        // le filtre par catégorie est plus précis que la recherche textuelle.
        // Sinon on utilise les keywords nettoyés, ou la requête brute en fallback.
        $keyword = $categoryId ? null : (implode(' ', $keywords) ?: $userQuery);

        // Construire les filtres pour le ProviderService
        $searchFilters = array_filter(array_merge([
            'keyword'        => $keyword,
            'city'           => $filters['city'],
            'category_id'    => $categoryId,
            'subcategory_id' => $subcategoryId,
            'min_rating'     => $filters['min_rating'],
            'is_open'        => $filters['is_open'],
            'per_page'       => 20,
        ], $location), fn($v) => !is_null($v) && $v !== '' && $v !== false);

        $providers = $this->providerClient->search($searchFilters);

        $responseTimeMs = (int) $startTime->diffInMilliseconds(now());

        AiSearchLog::create([
            'query'            => $userQuery,
            'parsed_filters'   => $filters,
            'results_count'    => count($providers),
            'response_time_ms' => $responseTimeMs,
        ]);

        return [
            'query'            => $userQuery,
            'filters'          => $filters,
            'search_mode'      => $filters['search_mode'],
            'providers'        => $providers,
            'total'            => count($providers),
            'response_time_ms' => $responseTimeMs,
        ];
    }
}
