<?php

namespace App\Repositories;

use App\Models\Trip;
use App\Models\User;
use App\Repositories\Contracts\TripRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TripRepository implements TripRepositoryInterface
{
    public function paginateForUser(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $user->trips()
            ->with(['segments.flight.airline', 'segments.flight.departureAirport', 'segments.flight.arrivalAirport'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function create(User $user, array $data): Trip
    {
        return $user->trips()->create($data);
    }

    public function findWithSegments(Trip $trip): Trip
    {
        return $trip->load(['segments.flight.airline', 'segments.flight.departureAirport', 'segments.flight.arrivalAirport']);
    }

    public function update(Trip $trip, array $data): Trip
    {
        $trip->update($data);

        return $trip->fresh();
    }

    public function delete(Trip $trip): void
    {
        $trip->delete();
    }
}
