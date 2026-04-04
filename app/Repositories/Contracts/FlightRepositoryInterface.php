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

    public function find(int $id): Flight;

    public function create(array $data): Flight;

    public function update(int $id, array $data): Flight;

    public function delete(int $id): void;

    public function findWithRelations(Flight $flight): Flight;

    public function count(): int;
}
