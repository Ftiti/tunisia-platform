<?php
namespace App\Repositories\Contracts;

use App\Models\Provider;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

interface ProviderRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator;
    public function findById(int $id): ?Provider;
    public function findBySlug(string $slug): ?Provider;
    public function create(array $data): Provider;
    public function update(Provider $provider, array $data): Provider;
    public function delete(Provider $provider): bool;
    public function getNearby(float $lat, float $lng, float $radius, array $filters): Builder;
}
