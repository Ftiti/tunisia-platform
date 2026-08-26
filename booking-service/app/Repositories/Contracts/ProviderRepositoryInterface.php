<?php
namespace App\Repositories\Contracts;

use App\Models\Provider;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProviderRepositoryInterface
{
    public function findById(int $id): ?Provider;
    public function getByFilters(array $filters, int $perPage = 15): LengthAwarePaginator;
    public function create(array $data): Provider;
    public function update(int $id, array $data): Provider;
}
