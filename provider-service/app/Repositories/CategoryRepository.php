<?php
namespace App\Repositories;

use App\Models\ProviderCategory;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository implements CategoryRepositoryInterface
{
    /** All categories (active + inactive), most recently modified first — back-office */
    public function getAll(): Collection
    {
        return ProviderCategory::withCount('providers')
            ->roots()
            ->with(['children' => fn($q) => $q->withCount('providers')->orderBy('updated_at', 'desc')])
            ->orderBy('updated_at', 'desc')->get();
    }

    /** Active categories only, ordered by display order — mobile / public API */
    public function getAllActive(): Collection
    {
        return ProviderCategory::withCount('providers')
            ->active()->roots()
            ->with(['children' => fn($q) => $q->active()->withCount('providers')])
            ->orderBy('order')->orderBy('name')->get();
    }

    public function findById(int $id): ?ProviderCategory
    {
        return ProviderCategory::with(['children','parent'])->find($id);
    }

    public function create(array $data): ProviderCategory { return ProviderCategory::create($data); }

    public function update(ProviderCategory $cat, array $data): ProviderCategory
    {
        $cat->update($data);
        return $cat->fresh();
    }

    public function delete(ProviderCategory $cat): bool { return $cat->delete(); }
}
