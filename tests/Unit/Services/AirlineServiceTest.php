<?php

namespace Tests\Unit\Services;

use App\Models\Airline;
use App\Repositories\Contracts\AirlineRepositoryInterface;
use App\Services\AirlineService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AirlineServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private AirlineRepositoryInterface $repository;

    private AirlineService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(AirlineRepositoryInterface::class);
        $this->service = new AirlineService($this->repository);
    }

    public function test_list_delegates_to_repository_paginate(): void
    {
        $filters = ['search' => 'Air Canada', 'sort' => 'recent'];
        $paginator = new LengthAwarePaginator([], 0, 15, 1);

        $this->repository
            ->shouldReceive('paginate')
            ->once()
            ->with(15, $filters)
            ->andReturn($paginator);

        $result = $this->service->list(15, $filters);

        $this->assertSame($paginator, $result);
    }

    public function test_find_returns_repository_result(): void
    {
        $airline = new Airline(['name' => 'Air Canada', 'iata_code' => 'AC']);

        $this->repository
            ->shouldReceive('find')
            ->once()
            ->with(10)
            ->andReturn($airline);

        $this->assertSame($airline, $this->service->find(10));
    }

    public function test_create_returns_repository_result(): void
    {
        $payload = ['name' => 'Air Canada', 'iata_code' => 'AC'];
        $airline = new Airline($payload);

        $this->repository
            ->shouldReceive('create')
            ->once()
            ->with($payload)
            ->andReturn($airline);

        $this->assertSame($airline, $this->service->create($payload));
    }

    public function test_update_returns_repository_result(): void
    {
        $payload = ['name' => 'Updated Airline'];
        $airline = new Airline(['name' => 'Updated Airline', 'iata_code' => 'AC']);

        $this->repository
            ->shouldReceive('update')
            ->once()
            ->with(7, $payload)
            ->andReturn($airline);

        $this->assertSame($airline, $this->service->update(7, $payload));
    }

    public function test_delete_delegates_to_repository(): void
    {
        $this->repository
            ->shouldReceive('delete')
            ->once()
            ->with(9);

        $this->service->delete(9);

        $this->assertTrue(true);
    }

    public function test_get_with_flights_returns_repository_result(): void
    {
        $airline = new Airline(['name' => 'Air Canada', 'iata_code' => 'AC']);

        $this->repository
            ->shouldReceive('findWithFlights')
            ->once()
            ->with($airline)
            ->andReturn($airline);

        $this->assertSame($airline, $this->service->getWithFlights($airline));
    }

    public function test_count_returns_repository_count(): void
    {
        $this->repository
            ->shouldReceive('count')
            ->once()
            ->andReturn(42);

        $this->assertSame(42, $this->service->count());
    }

    public function test_all_returns_repository_collection(): void
    {
        $collection = new EloquentCollection([
            new Airline(['name' => 'Air Canada', 'iata_code' => 'AC']),
            new Airline(['name' => 'WestJet', 'iata_code' => 'WS']),
        ]);

        $this->repository
            ->shouldReceive('all')
            ->once()
            ->andReturn($collection);

        $this->assertSame($collection, $this->service->all());
    }
}
