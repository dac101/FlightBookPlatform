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
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        try {
            $query = Airline::query();

            if (! empty($filters['search'])) {
                $query->where(function ($builder) use ($filters): void {
                    $builder->where('name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('iata_code', 'like', '%'.strtoupper($filters['search']).'%');
                });
            }

            if (($filters['sort'] ?? null) === 'recent') {
                $query->orderByDesc('created_at');
            } else {
                $query->orderBy('name');
            }

            return $query->paginate($perPage)->withQueryString();
        } catch (Throwable $e) {
            Log::error('AirlineRepository: error paginating', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function find(int $id): Airline
    {
        try {
            return Airline::findOrFail($id);
        } catch (Throwable $e) {
            Log::error('AirlineRepository: error finding', ['id' => $id, 'message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function create(array $data): Airline
    {
        try {
            $data['iata_code'] = strtoupper($data['iata_code']);

            return Airline::create($data);
        } catch (Throwable $e) {
            Log::error('AirlineRepository: error creating', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(int $id, array $data): Airline
    {
        try {
            $airline = $this->find($id);

            if (isset($data['iata_code'])) {
                $data['iata_code'] = strtoupper($data['iata_code']);
            }

            $airline->update($data);

            return $airline->fresh();
        } catch (Throwable $e) {
            Log::error('AirlineRepository: error updating', ['id' => $id, 'message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        try {
            $this->find($id)->delete();
        } catch (Throwable $e) {
            Log::error('AirlineRepository: error deleting', ['id' => $id, 'message' => $e->getMessage()]);
            throw $e;
        }
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
