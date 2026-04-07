<?php

namespace Tests\Unit\Services;

use App\Models\Airport;
use App\Repositories\Contracts\AirportRepositoryInterface;
use App\Services\AirportService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AirportServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private AirportRepositoryInterface $repository;

    private AirportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(AirportRepositoryInterface::class);
        $this->service = new AirportService($this->repository);
    }

    public function test_list_delegates_to_repository_paginate(): void
    {
        $filters = ['search' => 'YUL', 'sort' => 'recent'];
        $paginator = new LengthAwarePaginator([], 0, 15, 1);

        $this->repository->shouldReceive('paginate')->once()->with(15, $filters)->andReturn($paginator);

        $this->assertSame($paginator, $this->service->list(15, $filters));
    }

    public function test_find_returns_repository_result(): void
    {
        $airport = new Airport(['name' => 'Montreal Trudeau', 'iata_code' => 'YUL']);

        $this->repository->shouldReceive('find')->once()->with(4)->andReturn($airport);

        $this->assertSame($airport, $this->service->find(4));
    }

    public function test_create_returns_repository_result(): void
    {
        $payload = ['name' => 'Montreal Trudeau', 'iata_code' => 'YUL'];
        $airport = new Airport($payload);

        $this->repository->shouldReceive('create')->once()->with($payload)->andReturn($airport);

        $this->assertSame($airport, $this->service->create($payload));
    }

    public function test_update_returns_repository_result(): void
    {
        $payload = ['name' => 'Updated Airport'];
        $airport = new Airport(['name' => 'Updated Airport', 'iata_code' => 'YUL']);

        $this->repository->shouldReceive('update')->once()->with(6, $payload)->andReturn($airport);

        $this->assertSame($airport, $this->service->update(6, $payload));
    }

    public function test_delete_delegates_to_repository(): void
    {
        $this->repository->shouldReceive('delete')->once()->with(8);

        $this->service->delete(8);

        $this->assertTrue(true);
    }

    public function test_get_with_flights_returns_repository_result(): void
    {
        $airport = new Airport(['name' => 'Montreal Trudeau', 'iata_code' => 'YUL']);

        $this->repository->shouldReceive('findWithFlights')->once()->with($airport)->andReturn($airport);

        $this->assertSame($airport, $this->service->getWithFlights($airport));
    }

    public function test_count_returns_repository_count(): void
    {
        $this->repository->shouldReceive('count')->once()->andReturn(12);

        $this->assertSame(12, $this->service->count());
    }

    public function test_all_returns_repository_collection(): void
    {
        $collection = new EloquentCollection([
            new Airport(['name' => 'Montreal Trudeau', 'iata_code' => 'YUL']),
            new Airport(['name' => 'Toronto Pearson', 'iata_code' => 'YYZ']),
        ]);

        $this->repository->shouldReceive('all')->once()->andReturn($collection);

        $this->assertSame($collection, $this->service->all());
    }

    public function test_search_suggestions_returns_repository_collection(): void
    {
        $collection = new EloquentCollection([
            new Airport(['name' => 'Montreal Trudeau', 'iata_code' => 'YUL']),
        ]);

        $this->repository->shouldReceive('searchSuggestions')->once()->with('Montreal', 5)->andReturn($collection);

        $this->assertSame($collection, $this->service->searchSuggestions('Montreal', 5));
    }

    public function test_find_nearby_returns_repository_collection(): void
    {
        $collection = new EloquentCollection([
            new Airport(['name' => 'Montreal Trudeau', 'iata_code' => 'YUL']),
        ]);

        $this->repository->shouldReceive('findNearby')->once()->with(45.47, -73.74, 150.0, 8)->andReturn($collection);

        $this->assertSame($collection, $this->service->findNearby(45.47, -73.74, 150.0, 8));
    }
}
