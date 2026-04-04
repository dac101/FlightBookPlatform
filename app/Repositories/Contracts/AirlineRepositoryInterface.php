<?php

namespace App\Repositories\Contracts;

use App\Models\Airline;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface AirlineRepositoryInterface
{
    /**
     * @param  array{search?: string, sort?: string}  $filters
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    public function find(int $id): Airline;

    public function create(array $data): Airline;

    public function update(int $id, array $data): Airline;

    public function delete(int $id): void;

    public function findWithFlights(Airline $airline): Airline;

    public function count(): int;

    public function all(): Collection;
}
