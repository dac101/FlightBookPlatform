<?php

namespace App\Repositories;

use App\Models\Trip;
use App\Models\User;
use App\Repositories\Contracts\TripRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TripRepository implements TripRepositoryInterface
{
    public function paginateAll(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        try {
            $query = Trip::query()
                ->with([
                    'user',
                    'segments.flight.airline',
                    'segments.flight.departureAirport',
                    'segments.flight.arrivalAirport',
                ]);

            if (! empty($filters['search'])) {
                $search = $filters['search'];

                $query->where(function ($builder) use ($search): void {
                    $builder->whereHas('user', function ($userQuery) use ($search): void {
                        $userQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    })->orWhere('trip_name', 'like', '%'.$search.'%')
                        ->orWhere('id', $search);
                });
            }

            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (! empty($filters['trip_type'])) {
                $query->where('trip_type', $filters['trip_type']);
            }

            if (($filters['sort'] ?? null) === 'oldest') {
                $query->orderBy('created_at');
            } else {
                $query->orderByDesc('created_at');
            }

            return $query->paginate($perPage)->withQueryString();
        } catch (Throwable $e) {
            Log::error('TripRepository: error paginating all trips', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

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

    public function createWithSegments(User $user, array $tripData, array $segments): Trip
    {
        return DB::transaction(function () use ($user, $tripData, $segments): Trip {
            $trip = $user->trips()->create($tripData);

            foreach ($segments as $segment) {
                $trip->segments()->create([
                    'flight_id' => $segment['flight_id'],
                    'segment_order' => $segment['segment_order'],
                    'departure_date' => $segment['departure_date'],
                    'segment_type' => $segment['segment_type'],
                ]);
            }

            return $trip->fresh();
        });
    }

    public function find(int $id): Trip
    {
        try {
            return Trip::findOrFail($id);
        } catch (Throwable $e) {
            Log::error('TripRepository: error finding trip', ['id' => $id, 'message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function findWithSegments(Trip $trip): Trip
    {
        return $trip->load(['user', 'segments.flight.airline', 'segments.flight.departureAirport', 'segments.flight.arrivalAirport']);
    }

    public function update(Trip $trip, array $data): Trip
    {
        $trip->update($data);

        return $trip->fresh();
    }

    public function appendSegment(Trip $trip, array $segmentData, array $tripData = []): Trip
    {
        return DB::transaction(function () use ($trip, $segmentData, $tripData): Trip {
            if ($tripData !== []) {
                $trip->update($tripData);
            }

            $nextOrder = (int) $trip->segments()->max('segment_order') + 1;

            $trip->segments()->create([
                'flight_id' => $segmentData['flight_id'],
                'segment_order' => $nextOrder,
                'departure_date' => $segmentData['departure_date'],
                'segment_type' => $segmentData['segment_type'],
            ]);

            return $trip->fresh();
        });
    }

    public function delete(Trip $trip): void
    {
        $trip->delete();
    }

    public function count(): int
    {
        try {
            return Trip::count();
        } catch (Throwable $e) {
            Log::error('TripRepository: error counting trips', ['message' => $e->getMessage()]);

            return 0;
        }
    }
}
