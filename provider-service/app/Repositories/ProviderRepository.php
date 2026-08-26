<?php
namespace App\Repositories;

use App\Models\Provider;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProviderRepository implements ProviderRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $q = Provider::query()->with(['category','schedules','images'])->active();

        if (!empty($filters['category_id'])) $q->byCategory((int)$filters['category_id']);
        if (!empty($filters['city']))         $q->byCity($filters['city']);
        if (!empty($filters['governorate']))  $q->byGovernorate($filters['governorate']);
        if (!empty($filters['is_verified']))  $q->verified();
        if (!empty($filters['is_featured']))  $q->featured();
        if (!empty($filters['min_rating']))   $q->where('rating', '>=', (float)$filters['min_rating']);
        if (!empty($filters['keyword'])) {
            $kw = $filters['keyword'];
            $q->where(fn($s) => $s->where('name','ILIKE',"%{$kw}%")
                                   ->orWhere('description','ILIKE',"%{$kw}%")
                                   ->orWhere('address','ILIKE',"%{$kw}%"));
        }

        match ($filters['sort_by'] ?? 'rating') {
            'newest'   => $q->orderByDesc('created_at'),
            'featured' => $q->orderByDesc('is_featured')->orderByDesc('rating'),
            default    => $q->orderByDesc('rating'),
        };

        return $q->paginate(min((int)($filters['per_page'] ?? 15), 50));
    }

    public function findById(int $id): ?Provider
    {
        return Provider::with(['category','services','schedules','reviews','images'])->find($id);
    }

    public function findBySlug(string $slug): ?Provider
    {
        return Provider::with(['category','services','schedules','reviews','images'])
            ->where('slug', $slug)->first();
    }

    public function create(array $data): Provider { return Provider::create($data); }

    public function update(Provider $provider, array $data): Provider
    {
        $provider->update($data);
        return $provider->fresh();
    }

    public function delete(Provider $provider): bool { return $provider->delete(); }

    public function getNearby(float $lat, float $lng, float $radius, array $filters): Builder
    {
        $q = Provider::query()->with(['category','schedules'])->active()->nearby($lat, $lng, $radius);
        if (!empty($filters['category_id'])) $q->byCategory((int)$filters['category_id']);
        if (!empty($filters['is_verified']))  $q->verified();
        if (!empty($filters['min_rating']))   $q->where('rating', '>=', (float)$filters['min_rating']);
        return $q;
    }
}
