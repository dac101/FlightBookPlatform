<?php

namespace App\Repositories;

use App\Models\Airport;
use App\Repositories\Contracts\AirportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class AirportRepository implements AirportRepositoryInterface
{
    public function paginate(int $perPage = 50): LengthAwarePaginator
    {
        return Airport::orderBy('city')->paginate($perPage);
    }

    public function findWithFlights(Airport $airport): Airport
    {
        return $airport->load('departingFlights', 'arrivingFlights');
    }

    public function count(): int
    {
        try {
            return Airport::count();
        } catch (Throwable $e) {
            Log::error('AirportRepository: error counting', ['message' => $e->getMessage()]);

            return 0;
        }
    }

    public function all(): Collection
    {
        try {
            return Airport::orderBy('city')->get(['id', 'name', 'iata_code', 'city']);
        } catch (Throwable $e) {
            Log::error('AirportRepository: error fetching all', ['message' => $e->getMessage()]);
            throw $e;
        }
    }
}
