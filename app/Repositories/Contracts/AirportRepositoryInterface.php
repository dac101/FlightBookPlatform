<?php

namespace App\Repositories\Contracts;

use App\Models\Airport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface AirportRepositoryInterface
{
    public function paginate(int $perPage = 50): LengthAwarePaginator;

    public function findWithFlights(Airport $airport): Airport;

    public function count(): int;

    public function all(): Collection;
}
