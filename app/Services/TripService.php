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

    public function listForUser(User $user): LengthAwarePaginator
    {
        return $this->tripRepository->paginateForUser($user);
    }

    public function create(User $user, array $data): Trip
    {
        return $this->tripRepository->create($user, $data);
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

    public function delete(Trip $trip): void
    {
        $this->tripRepository->delete($trip);
    }

    public function count(): int
    {
        return $this->tripRepository->count();
    }
}
