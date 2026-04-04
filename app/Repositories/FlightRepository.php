<?php

namespace App\Repositories;

use App\Models\Flight;
use App\Repositories\Contracts\FlightRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Log;
use Throwable;

class FlightRepository implements FlightRepositoryInterface
{
    /**
     * @param  array{search?: string, departure?: string, arrival?: string, airline?: string, sort?: string}  $filters
     */
    public function filter(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Flight::with(['airline', 'departureAirport', 'arrivalAirport']);

        if (! empty($filters['search'])) {
            $query->where('flight_number', 'like', '%'.strtoupper($filters['search']).'%');
        }

        if (! empty($filters['departure'])) {
            $query->whereHas('departureAirport', function ($q) use ($filters): void {
                $q->where('iata_code', strtoupper($filters['departure']));
            });
        }

        if (! empty($filters['arrival'])) {
            $query->whereHas('arrivalAirport', function ($q) use ($filters): void {
                $q->where('iata_code', strtoupper($filters['arrival']));
            });
        }

        if (! empty($filters['airline'])) {
            $query->whereHas('airline', function ($q) use ($filters): void {
                $q->where('iata_code', strtoupper($filters['airline']));
            });
        }

        if (($filters['sort'] ?? null) === 'recent') {
            $query->orderByDesc('created_at');
        } else {
            $query->orderBy('flight_number');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function searchForTripBuilder(array $criteria, int $perPage = 10): LengthAwarePaginator
    {
        $query = Flight::query()
            ->with(['airline', 'departureAirport', 'arrivalAirport'])
            ->whereIn('airport_departure_id', $criteria['departure_airport_ids'])
            ->whereIn('airport_arrival_id', $criteria['arrival_airport_ids']);

        if (! empty($criteria['preferred_airline_ids'])) {
            $query->whereIn('airline_id', $criteria['preferred_airline_ids']);
        }

        if (! empty($criteria['search'])) {
            $search = trim($criteria['search']);
            $searchCode = strtoupper($search);

            $query->where(function ($builder) use ($search, $searchCode): void {
                $builder->where('flight_number', 'like', '%'.$searchCode.'%')
                    ->orWhereHas('airline', function ($airlineQuery) use ($search, $searchCode): void {
                        $airlineQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('iata_code', 'like', '%'.$searchCode.'%');
                    })
                    ->orWhereHas('departureAirport', function ($airportQuery) use ($search, $searchCode): void {
                        $airportQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('city', 'like', '%'.$search.'%')
                            ->orWhere('iata_code', 'like', '%'.$searchCode.'%')
                            ->orWhere('city_code', 'like', '%'.$searchCode.'%');
                    })
                    ->orWhereHas('arrivalAirport', function ($airportQuery) use ($search, $searchCode): void {
                        $airportQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('city', 'like', '%'.$search.'%')
                            ->orWhere('iata_code', 'like', '%'.$searchCode.'%')
                            ->orWhere('city_code', 'like', '%'.$searchCode.'%');
                    });
            });
        }

        $flights = $query->get()->map(function (Flight $flight) {
            $flight->setAttribute('duration_minutes', $flight->durationMinutes());
            $flight->setAttribute('duration_label', $flight->durationLabel());

            return $flight;
        });

        $sortedFlights = (match ($criteria['sort'] ?? 'price') {
            'departure_time' => $flights->sortBy('departure_time'),
            'arrival_time' => $flights->sortBy('arrival_time'),
            'duration' => $flights->sortBy('duration_minutes'),
            default => $flights->sortBy('price'),
        })->values();

        $page = max(1, (int) ($criteria['page'] ?? 1));
        $items = $sortedFlights->forPage($page, $perPage)->values();

        return new Paginator(
            $items,
            $sortedFlights->count(),
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    public function find(int $id): Flight
    {
        try {
            return Flight::findOrFail($id);
        } catch (Throwable $e) {
            Log::error('FlightRepository: error finding', ['id' => $id, 'message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function create(array $data): Flight
    {
        try {
            $data['flight_number'] = strtoupper($data['flight_number']);

            return Flight::create($data)->load(['airline', 'departureAirport', 'arrivalAirport']);
        } catch (Throwable $e) {
            Log::error('FlightRepository: error creating', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(int $id, array $data): Flight
    {
        try {
            $flight = $this->find($id);

            if (isset($data['flight_number'])) {
                $data['flight_number'] = strtoupper($data['flight_number']);
            }

            $flight->update($data);

            return $flight->fresh()->load(['airline', 'departureAirport', 'arrivalAirport']);
        } catch (Throwable $e) {
            Log::error('FlightRepository: error updating', ['id' => $id, 'message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        try {
            $this->find($id)->delete();
        } catch (Throwable $e) {
            Log::error('FlightRepository: error deleting', ['id' => $id, 'message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function findWithRelations(Flight $flight): Flight
    {
        return $flight->load(['airline', 'departureAirport', 'arrivalAirport']);
    }

    public function count(): int
    {
        try {
            return Flight::count();
        } catch (Throwable $e) {
            Log::error('FlightRepository: error counting', ['message' => $e->getMessage()]);

            return 0;
        }
    }
}
