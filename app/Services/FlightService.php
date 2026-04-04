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
     * @param  array{departure?: string, arrival?: string, airline?: string}  $filters
     */
    public function search(array $filters): LengthAwarePaginator
    {
        return $this->flightRepository->filter($filters);
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
