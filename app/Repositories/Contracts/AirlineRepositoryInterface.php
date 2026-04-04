<?php

namespace App\Repositories\Contracts;

use App\Models\Airline;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface AirlineRepositoryInterface
{
    public function paginate(int $perPage = 50): LengthAwarePaginator;

    public function findWithFlights(Airline $airline): Airline;

    public function count(): int;

    public function all(): Collection;
}
