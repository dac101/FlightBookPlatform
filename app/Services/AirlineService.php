<?php

namespace App\Services;

use App\Models\Airline;
use App\Repositories\Contracts\AirlineRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AirlineService
{
    public function __construct(
        private readonly AirlineRepositoryInterface $airlineRepository
    ) {}

    public function list(): LengthAwarePaginator
    {
        return $this->airlineRepository->paginate();
    }

    public function getWithFlights(Airline $airline): Airline
    {
        return $this->airlineRepository->findWithFlights($airline);
    }

    public function count(): int
    {
        return $this->airlineRepository->count();
    }

    public function all(): Collection
    {
        return $this->airlineRepository->all();
    }
}
