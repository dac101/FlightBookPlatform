<?php

namespace App\Repositories\Contracts;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TripRepositoryInterface
{
    /**
     * @param  array{search?: string, status?: string, trip_type?: string, sort?: string}  $filters
     */
    public function paginateAll(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    public function paginateForUser(User $user, int $perPage = 20): LengthAwarePaginator;

    public function create(User $user, array $data): Trip;

    public function find(int $id): Trip;

    public function findWithSegments(Trip $trip): Trip;

    public function update(Trip $trip, array $data): Trip;

    public function delete(Trip $trip): void;

    public function count(): int;
}
