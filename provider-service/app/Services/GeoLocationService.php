<?php
namespace App\Services;

use App\Models\Provider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GeoLocationService
{
    /** Recherche prestataires proches avec PostGIS ST_DWithin */
    public function findNearby(float $lat, float $lng, float $radius = 10, array $filters = []): Builder
    {
        $q = Provider::query()
            ->with(['category','schedules'])
            ->active()
            ->selectRaw(
                "providers.*, ST_Distance(location::geography, ST_SetSRID(ST_Point(?,?)::geography,4326))/1000 AS distance_km",
                [$lng, $lat]
            )
            ->whereRaw(
                "location IS NOT NULL AND ST_DWithin(location::geography, ST_SetSRID(ST_Point(?,?)::geography,4326), ?)",
                [$lng, $lat, $radius * 1000]
            );

        if (!empty($filters['category_id'])) $q->byCategory((int)$filters['category_id']);
        if (!empty($filters['city']))         $q->byCity($filters['city']);
        if (!empty($filters['is_verified']))  $q->verified();
        if (!empty($filters['min_rating']))   $q->where('rating', '>=', (float)$filters['min_rating']);
        if (!empty($filters['is_open']))      $this->filterOpenNow($q);

        return $q->orderBy('distance_km');
    }

    /** Formule Haversine — distance en km entre deux coordonnées GPS */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2)**2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /** Sauvegarder coordonnées GPS dans PostGIS */
    public function saveLocation(Provider $provider, float $lat, float $lng): void
    {
        DB::statement(
            "UPDATE providers SET location = ST_SetSRID(ST_Point(?, ?), 4326) WHERE id = ?",
            [$lng, $lat, $provider->id]
        );
    }

    /** Lire les coordonnées depuis PostGIS */
    public function getCoordinates(Provider $provider): ?array
    {
        $r = DB::selectOne(
            "SELECT ST_X(location) as lng, ST_Y(location) as lat FROM providers WHERE id = ? AND location IS NOT NULL",
            [$provider->id]
        );
        return $r ? ['lat' => (float)$r->lat, 'lng' => (float)$r->lng] : null;
    }

    private function filterOpenNow(Builder $q): Builder
    {
        $day  = now()->dayOfWeek;
        $time = now()->format('H:i:s');
        return $q->whereHas('schedules', fn($s) =>
            $s->where('day_of_week', $day)->where('is_closed', false)
              ->where('open_time', '<=', $time)->where('close_time', '>=', $time)
        );
    }
}
