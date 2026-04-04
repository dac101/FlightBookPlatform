<?php

namespace App\Repositories;

use App\Models\Airline;
use App\Repositories\Contracts\AirlineRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class AirlineRepository implements AirlineRepositoryInterface
{
    public function paginate(int $perPage = 50): LengthAwarePaginator
    {
        return Airline::orderBy('name')->paginate($perPage);
    }

    public function findWithFlights(Airline $airline): Airline
    {
        return $airline->load('flights');
    }

    public function count(): int
    {
        try {
            return Airline::count();
        } catch (Throwable $e) {
            Log::error('AirlineRepository: error counting', ['message' => $e->getMessage()]);

            return 0;
        }
    }

    public function all(): Collection
    {
        try {
            return Airline::orderBy('name')->get(['id', 'name', 'iata_code']);
        } catch (Throwable $e) {
            Log::error('AirlineRepository: error fetching all', ['message' => $e->getMessage()]);
            throw $e;
        }
    }
}
