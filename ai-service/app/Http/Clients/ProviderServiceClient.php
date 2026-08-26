<?php

namespace App\Http\Clients;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    /**
     * Rechercher des prestataires avec filtres.
     *
     * @param  array $filters  keyword, city, category_id, min_rating, is_open, per_page, etc.
     * @return array           Liste des prestataires
     */
    public function search(array $filters = []): array
    {
        try {
            $response = Http::timeout(15)
                ->get("{$this->baseUrl}/providers", $filters);

            if ($response->failed()) {
                Log::warning('ProviderServiceClient::search failed', [
                    'status'  => $response->status(),
                    'filters' => $filters,
                ]);
                return [];
            }

            return $response->json('data.providers') ?? [];

        } catch (\Throwable $e) {
            Log::error('ProviderServiceClient::search error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Récupérer un prestataire par ID.
     */
    public function getProvider(int $id): ?array
    {
        try {
            $response = Http::timeout(10)
                ->get("{$this->baseUrl}/providers/{$id}");

            if ($response->failed()) {
                return null;
            }

            return $response->json('data') ?? null;

        } catch (\Throwable $e) {
            Log::error('ProviderServiceClient::getProvider error', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Récupérer les services d'un prestataire.
     */
    public function getServices(int $providerId): array
    {
        try {
            $response = Http::timeout(10)
                ->get("{$this->baseUrl}/providers/{$providerId}/services");

            return $response->json('data') ?? [];

        } catch (\Throwable $e) {
            Log::error('ProviderServiceClient::getServices error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Récupérer les catégories disponibles.
     */
    public function getCategories(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/categories");
            return $response->json('data') ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
