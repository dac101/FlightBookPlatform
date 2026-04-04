<?php

namespace App\Repositories\Contracts;

use App\Models\Flight;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface FlightRepositoryInterface
{
    /**
     * @param  array{departure?: string, arrival?: string, airline?: string}  $filters
     */
    public function filter(array $filters, int $perPage = 50): LengthAwarePaginator;

    public function findWithRelations(Flight $flight): Flight;

    public function count(): int;
}
