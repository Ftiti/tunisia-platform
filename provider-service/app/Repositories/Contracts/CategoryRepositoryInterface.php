<?php
namespace App\Repositories\Contracts;

use App\Models\ProviderCategory;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface
{
    public function getAll(): Collection;
    public function getAllActive(): Collection;
    public function findById(int $id): ?ProviderCategory;
    public function create(array $data): ProviderCategory;
    public function update(ProviderCategory $category, array $data): ProviderCategory;
    public function delete(ProviderCategory $category): bool;
}
