<?php

namespace App\Services;

use App\Models\Airport;
use App\Repositories\Contracts\AirportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AirportService
{
    public function __construct(
        private readonly AirportRepositoryInterface $airportRepository
    ) {}

    /**
     * @param  array{search?: string, sort?: string}  $filters
     */
    public function list(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->airportRepository->paginate($perPage, $filters);
    }

    public function find(int $id): Airport
    {
        return $this->airportRepository->find($id);
    }

    public function create(array $data): Airport
    {
        return $this->airportRepository->create($data);
    }

    public function update(int $id, array $data): Airport
    {
        return $this->airportRepository->update($id, $data);
    }

    public function delete(int $id): void
    {
        $this->airportRepository->delete($id);
    }

    public function getWithFlights(Airport $airport): Airport
    {
        return $this->airportRepository->findWithFlights($airport);
    }

    public function count(): int
    {
        return $this->airportRepository->count();
    }

    public function all(): Collection
    {
        return $this->airportRepository->all();
    }

    public function searchSuggestions(string $query, int $limit = 8): Collection
    {
        return $this->airportRepository->searchSuggestions($query, $limit);
    }

    public function findNearby(float $latitude, float $longitude, float $radiusKm, int $limit = 8): Collection
    {
        return $this->airportRepository->findNearby($latitude, $longitude, $radiusKm, $limit);
    }
}
