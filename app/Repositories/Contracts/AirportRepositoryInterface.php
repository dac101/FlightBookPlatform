<?php

namespace App\Repositories\Contracts;

use App\Models\Airport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface AirportRepositoryInterface
{
    /**
     * @param  array{search?: string, sort?: string}  $filters
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    public function find(int $id): Airport;

    public function create(array $data): Airport;

    public function update(int $id, array $data): Airport;

    public function delete(int $id): void;

    public function findWithFlights(Airport $airport): Airport;

    public function count(): int;

    public function all(): Collection;

    public function searchSuggestions(string $query, int $limit = 8): Collection;

    public function findNearby(float $latitude, float $longitude, float $radiusKm, int $limit = 8): Collection;

    public function forMap(): Collection;
}
