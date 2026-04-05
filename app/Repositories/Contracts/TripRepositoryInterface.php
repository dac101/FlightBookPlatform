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

    /**
     * @param  array<int, array{
     *   flight_id: int,
     *   segment_order: int,
     *   departure_date: string,
     *   segment_type: string
     * }>  $segments
     */
    public function createWithSegments(User $user, array $tripData, array $segments): Trip;

    public function find(int $id): Trip;

    public function findWithSegments(Trip $trip): Trip;

    public function update(Trip $trip, array $data): Trip;

    public function appendSegment(Trip $trip, array $segmentData, array $tripData = []): Trip;

    public function delete(Trip $trip): void;

    public function count(): int;
}
