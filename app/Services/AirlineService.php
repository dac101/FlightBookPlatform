<?php

namespace App\Services;

use App\Models\Airline;
use App\Repositories\Contracts\AirlineRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AirlineService
{
    public function __construct(
        private readonly AirlineRepositoryInterface $airlineRepository
    ) {}

    /**
     * @param  array{search?: string, sort?: string}  $filters
     */
    public function list(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->airlineRepository->paginate($perPage, $filters);
    }

    public function find(int $id): Airline
    {
        return $this->airlineRepository->find($id);
    }

    public function create(array $data): Airline
    {
        return $this->airlineRepository->create($data);
    }

    public function update(int $id, array $data): Airline
    {
        return $this->airlineRepository->update($id, $data);
    }

    public function delete(int $id): void
    {
        $this->airlineRepository->delete($id);
    }

    public function getWithFlights(Airline $airline): Airline
    {
        return $this->airlineRepository->findWithFlights($airline);
    }

    public function count(): int
    {
        return $this->airlineRepository->count();
    }

    public function all(): Collection
    {
        return $this->airlineRepository->all();
    }
}
