<?php

namespace App\Repositories\Contracts;

use App\Models\Flight;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface FlightRepositoryInterface
{
    /**
     * @param  array{search?: string, departure?: string, arrival?: string, airline?: string, sort?: string}  $filters
     */
    public function filter(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * @param  array{
     *   departure_airport_ids: array<int>,
     *   arrival_airport_ids: array<int>,
     *   preferred_airline_ids?: array<int>,
     *   sort?: string,
     *   search?: string,
     *   page?: int
     * }  $criteria
     */
    public function searchForTripBuilder(array $criteria, int $perPage = 10): LengthAwarePaginator;

    public function find(int $id): Flight;

    public function create(array $data): Flight;

    public function update(int $id, array $data): Flight;

    public function delete(int $id): void;

    public function findWithRelations(Flight $flight): Flight;

    public function count(): int;
}
