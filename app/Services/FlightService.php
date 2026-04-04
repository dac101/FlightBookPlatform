<?php

namespace App\Services;

use App\Models\Flight;
use App\Repositories\Contracts\FlightRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FlightService
{
    public function __construct(
        private readonly FlightRepositoryInterface $flightRepository
    ) {}

    /**
     * @param  array{
     *   search?: string,
     *   departure?: string,
     *   arrival?: string,
     *   airline?: string,
     *   preferred_airline_ids?: array<int>,
     *   sort?: string
     * }  $filters
     */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->flightRepository->filter($filters, $perPage);
    }

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
    public function searchForTripBuilder(array $criteria, int $perPage = 10): LengthAwarePaginator
    {
        return $this->flightRepository->searchForTripBuilder($criteria, $perPage);
    }

    public function find(int $id): Flight
    {
        return $this->flightRepository->find($id);
    }

    public function create(array $data): Flight
    {
        return $this->flightRepository->create($data);
    }

    public function update(int $id, array $data): Flight
    {
        return $this->flightRepository->update($id, $data);
    }

    public function delete(int $id): void
    {
        $this->flightRepository->delete($id);
    }

    public function getWithRelations(Flight $flight): Flight
    {
        return $this->flightRepository->findWithRelations($flight);
    }

    public function count(): int
    {
        return $this->flightRepository->count();
    }
}
