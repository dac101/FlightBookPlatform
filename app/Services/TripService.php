<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\User;
use App\Repositories\Contracts\TripRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TripService
{
    public function __construct(
        private readonly TripRepositoryInterface $tripRepository
    ) {}

    /**
     * @param  array{search?: string, status?: string, trip_type?: string, sort?: string}  $filters
     */
    public function listAll(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->tripRepository->paginateAll($perPage, $filters);
    }

    public function listForUser(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $this->tripRepository->paginateForUser($user, $perPage);
    }

    public function create(User $user, array $data): Trip
    {
        return $this->tripRepository->create($user, $data);
    }

    /**
     * @param  array<int, array{
     *   flight_id: int,
     *   segment_order: int,
     *   departure_date: string,
     *   segment_type: string
     * }>  $segments
     */
    public function book(User $user, array $tripData, array $segments): Trip
    {
        $trip = $this->tripRepository->createWithSegments($user, $tripData, $segments);
        $trip->recalculateTotal();

        return $this->tripRepository->findWithSegments($trip->fresh());
    }

    public function getWithSegments(Trip $trip): Trip
    {
        return $this->tripRepository->findWithSegments($trip);
    }

    public function find(int $id): Trip
    {
        return $this->tripRepository->find($id);
    }

    public function update(Trip $trip, array $data): Trip
    {
        $trip = $this->tripRepository->update($trip, $data);
        $trip->recalculateTotal();

        return $trip->fresh();
    }

    public function appendSegment(Trip $trip, array $segmentData, array $tripData = []): Trip
    {
        $trip = $this->tripRepository->appendSegment($trip, $segmentData, $tripData);
        $trip->recalculateTotal();

        return $this->tripRepository->findWithSegments($trip->fresh());
    }

    public function replaceSegment(Trip $trip, array $segmentData, array $tripData = []): Trip
    {
        $trip = $this->tripRepository->replaceSegments($trip, $segmentData, $tripData);
        $trip->recalculateTotal();

        return $this->tripRepository->findWithSegments($trip->fresh());
    }

    /**
     * @param  array<int, array{
     *   flight_id: int,
     *   segment_order: int,
     *   departure_date: string,
     *   segment_type: string
     * }>  $segments
     */
    public function rebuildWithSegments(Trip $trip, array $tripData, array $segments): Trip
    {
        $trip = $this->tripRepository->rebuildWithSegments($trip, $tripData, $segments);
        $trip->recalculateTotal();

        return $this->tripRepository->findWithSegments($trip->fresh());
    }

    public function delete(Trip $trip): void
    {
        $this->tripRepository->delete($trip);
    }

    public function count(): int
    {
        return $this->tripRepository->count();
    }
}
