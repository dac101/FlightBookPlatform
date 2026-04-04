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
     * @param  array{
     *   search?: string,
     *   departure?: string,
     *   arrival?: string,
     *   airline?: string,
     *   preferred_airline_ids?: array<int>,
     *   sort?: string
     * }  $filters
     */
    public function filter(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Flight::with(['airline', 'departureAirport', 'arrivalAirport']);

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
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

        if (! empty($filters['departure'])) {
            $departure = trim($filters['departure']);
            $departureCode = strtoupper($departure);

            $query->whereHas('departureAirport', function ($q) use ($departure, $departureCode): void {
                $q->where('name', 'like', '%'.$departure.'%')
                    ->orWhere('city', 'like', '%'.$departure.'%')
                    ->orWhere('iata_code', 'like', '%'.$departureCode.'%')
                    ->orWhere('city_code', 'like', '%'.$departureCode.'%');
            });
        }

        if (! empty($filters['arrival'])) {
            $arrival = trim($filters['arrival']);
            $arrivalCode = strtoupper($arrival);

            $query->whereHas('arrivalAirport', function ($q) use ($arrival, $arrivalCode): void {
                $q->where('name', 'like', '%'.$arrival.'%')
                    ->orWhere('city', 'like', '%'.$arrival.'%')
                    ->orWhere('iata_code', 'like', '%'.$arrivalCode.'%')
                    ->orWhere('city_code', 'like', '%'.$arrivalCode.'%');
            });
        }

        if (! empty($filters['airline'])) {
            $query->whereHas('airline', function ($q) use ($filters): void {
                $q->where('name', 'like', '%'.$filters['airline'].'%')
                    ->orWhere('iata_code', 'like', '%'.strtoupper($filters['airline']).'%');
            });
        }

        if (! empty($filters['preferred_airline_ids'])) {
            $query->whereIn('airline_id', $filters['preferred_airline_ids']);
        }

        $query = match ($filters['sort'] ?? 'recent') {
            'departure_time' => $query->orderByDesc('departure_time')->orderByDesc('created_at'),
            'arrival_time' => $query->orderByDesc('arrival_time')->orderByDesc('created_at'),
            'price' => $query->orderBy('price')->orderByDesc('created_at'),
            default => $query->orderByDesc('created_at'),
        };

        return $query
            ->paginate($perPage)
            ->through(function (Flight $flight) {
                $flight->setAttribute('duration_minutes', $flight->durationMinutes());
                $flight->setAttribute('duration_label', $flight->durationLabel());

                return $flight;
            })
            ->withQueryString();
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
