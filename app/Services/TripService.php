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
}
