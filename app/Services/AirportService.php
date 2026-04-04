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

    public function list(): LengthAwarePaginator
    {
        return $this->airportRepository->paginate();
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
}
