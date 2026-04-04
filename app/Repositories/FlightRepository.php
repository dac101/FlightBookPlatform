<?php

namespace App\Repositories;

use App\Models\Flight;
use App\Repositories\Contracts\FlightRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Throwable;

class FlightRepository implements FlightRepositoryInterface
{
    /**
     * @param  array{departure?: string, arrival?: string, airline?: string}  $filters
     */
    public function filter(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $query = Flight::with(['airline', 'departureAirport', 'arrivalAirport']);

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

        return $query->paginate($perPage);
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
