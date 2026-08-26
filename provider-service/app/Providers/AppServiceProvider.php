<?php
namespace App\Providers;

use App\Models\AuthPersonalAccessToken;
use App\Repositories\CategoryRepository;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use App\Repositories\ProviderRepository;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProviderRepositoryInterface::class, ProviderRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
    }

    public function boot(): void
    {
        // Sanctum doit valider les tokens dans la DB de l'Auth Service (tunisia_platform),
        // pas dans la DB du Provider Service (tunisia_providers).
        Sanctum::usePersonalAccessTokenModel(AuthPersonalAccessToken::class);
    }
}
