<?php
namespace App\Repositories;

use App\Models\Provider;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProviderRepository implements ProviderRepositoryInterface
{
    public function __construct(private readonly Provider $model) {}

    public function findById(int $id): ?Provider
    {
        return $this->model->with(['category', 'services', 'schedules'])->find($id);
    }

    public function getByFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with('category')->active();

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (! empty($filters['city'])) {
            $query->where('city', 'ilike', '%' . $filters['city'] . '%');
        }
        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(fn($q) => $q->where('name', 'ilike', "%{$s}%")
                                      ->orWhere('description', 'ilike', "%{$s}%"));
        }
        // Tri par distance si coordonnées fournies
        if (! empty($filters['lat']) && ! empty($filters['lng'])) {
            $lat = (float) $filters['lat'];
            $lng = (float) $filters['lng'];
            $query->selectRaw('*, ST_Distance(location::geography, ST_SetSRID(ST_MakePoint(?,?),4326)::geography) AS distance', [$lng, $lat])
                  ->orderBy('distance');
        }

        return $query->paginate($perPage);
    }

    public function create(array $data): Provider
    {
        // Construire le POINT PostGIS si lat/lng fournis
        if (isset($data['lat'], $data['lng'])) {
            $lat = $data['lat'];
            $lng = $data['lng'];
            unset($data['lat'], $data['lng']);
            $provider = $this->model->create($data);
            DB::statement(
                "UPDATE providers SET location = ST_SetSRID(ST_MakePoint(?,?),4326) WHERE id = ?",
                [$lng, $lat, $provider->id]
            );
            return $provider->fresh();
        }
        return $this->model->create($data);
    }

    public function update(int $id, array $data): Provider
    {
        $provider = $this->model->findOrFail($id);
        if (isset($data['lat'], $data['lng'])) {
            $lat = $data['lat'];
            $lng = $data['lng'];
            unset($data['lat'], $data['lng']);
            DB::statement(
                "UPDATE providers SET location = ST_SetSRID(ST_MakePoint(?,?),4326) WHERE id = ?",
                [$lng, $lat, $id]
            );
        }
        $provider->update($data);
        return $provider->fresh(['category', 'services', 'schedules']);
    }
}
