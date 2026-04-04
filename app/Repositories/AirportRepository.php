<?php

namespace App\Repositories;

use App\Models\Airport;
use App\Repositories\Contracts\AirportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class AirportRepository implements AirportRepositoryInterface
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        try {
            $query = Airport::query();

            if (! empty($filters['search'])) {
                $search = $filters['search'];
                $searchCode = strtoupper($search);

                $query->where(function ($builder) use ($search, $searchCode): void {
                    $builder->where('name', 'like', '%'.$search.'%')
                        ->orWhere('city', 'like', '%'.$search.'%')
                        ->orWhere('iata_code', 'like', '%'.$searchCode.'%')
                        ->orWhere('city_code', 'like', '%'.$searchCode.'%')
                        ->orWhere('country_code', 'like', '%'.$searchCode.'%');
                });
            }

            if (($filters['sort'] ?? null) === 'recent') {
                $query->orderByDesc('created_at');
            } else {
                $query->orderBy('city')->orderBy('name');
            }

            return $query->paginate($perPage)->withQueryString();
        } catch (Throwable $e) {
            Log::error('AirportRepository: error paginating', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function find(int $id): Airport
    {
        try {
            return Airport::findOrFail($id);
        } catch (Throwable $e) {
            Log::error('AirportRepository: error finding', ['id' => $id, 'message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function create(array $data): Airport
    {
        try {
            return Airport::create($this->normalizeCodes($data));
        } catch (Throwable $e) {
            Log::error('AirportRepository: error creating', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(int $id, array $data): Airport
    {
        try {
            $airport = $this->find($id);
            $airport->update($this->normalizeCodes($data));

            return $airport->fresh();
        } catch (Throwable $e) {
            Log::error('AirportRepository: error updating', ['id' => $id, 'message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        try {
            $this->find($id)->delete();
        } catch (Throwable $e) {
            Log::error('AirportRepository: error deleting', ['id' => $id, 'message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function findWithFlights(Airport $airport): Airport
    {
        return $airport->load('departingFlights', 'arrivingFlights');
    }

    public function count(): int
    {
        try {
            return Airport::count();
        } catch (Throwable $e) {
            Log::error('AirportRepository: error counting', ['message' => $e->getMessage()]);

            return 0;
        }
    }

    public function all(): Collection
    {
        try {
            return Airport::orderBy('city')->get(['id', 'name', 'iata_code', 'city', 'city_code', 'latitude', 'longitude']);
        } catch (Throwable $e) {
            Log::error('AirportRepository: error fetching all', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function searchSuggestions(string $query, int $limit = 8): Collection
    {
        try {
            $search = trim($query);
            $searchCode = strtoupper($search);

            return Airport::query()
                ->where(function ($builder) use ($search, $searchCode): void {
                    $builder->where('name', 'like', '%'.$search.'%')
                        ->orWhere('city', 'like', '%'.$search.'%')
                        ->orWhere('iata_code', 'like', '%'.$searchCode.'%')
                        ->orWhere('city_code', 'like', '%'.$searchCode.'%')
                        ->orWhere('country_code', 'like', '%'.$searchCode.'%');
                })
                ->orderBy('city')
                ->orderBy('name')
                ->limit($limit)
                ->get([
                    'id',
                    'name',
                    'city',
                    'iata_code',
                    'city_code',
                    'country_code',
                    'latitude',
                    'longitude',
                    'timezone',
                ]);
        } catch (Throwable $e) {
            Log::error('AirportRepository: error searching suggestions', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function findNearby(float $latitude, float $longitude, float $radiusKm, int $limit = 8): Collection
    {
        try {
            return Airport::query()
                ->get([
                    'id',
                    'name',
                    'city',
                    'iata_code',
                    'city_code',
                    'country_code',
                    'latitude',
                    'longitude',
                    'timezone',
                ])
                ->map(function (Airport $airport) use ($latitude, $longitude) {
                    $distance = $this->haversineDistance(
                        $latitude,
                        $longitude,
                        (float) $airport->latitude,
                        (float) $airport->longitude
                    );

                    $airport->setAttribute('distance_km', round($distance, 1));

                    return $airport;
                })
                ->filter(fn (Airport $airport) => $airport->distance_km <= $radiusKm)
                ->sortBy('distance_km')
                ->take($limit)
                ->values();
        } catch (Throwable $e) {
            Log::error('AirportRepository: error finding nearby airports', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    private function normalizeCodes(array $data): array
    {
        foreach (['iata_code', 'city_code', 'country_code', 'region_code'] as $field) {
            if (isset($data[$field]) && $data[$field] !== null) {
                $data[$field] = strtoupper($data[$field]);
            }
        }

        return $data;
    }

    private function haversineDistance(float $latitudeA, float $longitudeA, float $latitudeB, float $longitudeB): float
    {
        $earthRadiusKm = 6371;

        $latitudeDelta = deg2rad($latitudeB - $latitudeA);
        $longitudeDelta = deg2rad($longitudeB - $longitudeA);

        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($latitudeA)) * cos(deg2rad($latitudeB)) * sin($longitudeDelta / 2) ** 2;

        return 2 * $earthRadiusKm * asin(min(1, sqrt($a)));
    }
}
