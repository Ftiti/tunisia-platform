<?php

namespace App\Providers;

use App\AI\Agents\ChatbotAgent;
use App\AI\Agents\ClassificationAgent;
use App\AI\Agents\RecommendationAgent;
use App\AI\Agents\SearchAgent;
use App\AI\OllamaClient;
use App\Http\Clients\BookingServiceClient;
use App\Http\Clients\ProviderServiceClient;
use App\Models\AuthPersonalAccessToken;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Singletons infrastructure ─────────────────────────────────────────
        $this->app->singleton(OllamaClient::class);
        $this->app->singleton(ProviderServiceClient::class);
        $this->app->singleton(BookingServiceClient::class);

        // ── Agents IA ─────────────────────────────────────────────────────────
        $this->app->singleton(SearchAgent::class, fn($app) => new SearchAgent(
            $app->make(OllamaClient::class),
            $app->make(ProviderServiceClient::class),
        ));

        $this->app->singleton(ChatbotAgent::class, fn($app) => new ChatbotAgent(
            $app->make(OllamaClient::class),
            $app->make(BookingServiceClient::class),
        ));

        $this->app->singleton(RecommendationAgent::class, fn($app) => new RecommendationAgent(
            $app->make(OllamaClient::class),
            $app->make(ProviderServiceClient::class),
            $app->make(BookingServiceClient::class),
        ));

        $this->app->singleton(ClassificationAgent::class, fn($app) => new ClassificationAgent(
            $app->make(OllamaClient::class),
        ));
    }

    public function boot(): void
    {
        // Valider les Sanctum tokens depuis la DB de l'Auth Service (tunisia_platform)
        Sanctum::usePersonalAccessTokenModel(AuthPersonalAccessToken::class);
    }
}
