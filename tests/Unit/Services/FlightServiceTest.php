<?php

namespace Tests\Unit\Services;

use App\Models\Flight;
use App\Repositories\Contracts\FlightRepositoryInterface;
use App\Services\FlightService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class FlightServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private FlightRepositoryInterface $repository;

    private FlightService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(FlightRepositoryInterface::class);
        $this->service = new FlightService($this->repository);
    }

    public function test_search_delegates_to_repository_filter(): void
    {
        $filters = ['search' => 'AC', 'sort' => 'recent'];
        $paginator = new LengthAwarePaginator([], 0, 15, 1);

        $this->repository->shouldReceive('filter')->once()->with($filters, 15)->andReturn($paginator);

        $this->assertSame($paginator, $this->service->search($filters, 15));
    }

    public function test_search_for_trip_builder_delegates_to_repository(): void
    {
        $criteria = [
            'departure_airport_ids' => [1, 2],
            'arrival_airport_ids' => [3, 4],
            'preferred_airline_ids' => [5],
            'sort' => 'price',
            'search' => 'AC',
            'page' => 2,
        ];
        $paginator = new LengthAwarePaginator([], 0, 10, 1);

        $this->repository->shouldReceive('searchForTripBuilder')->once()->with($criteria, 10)->andReturn($paginator);

        $this->assertSame($paginator, $this->service->searchForTripBuilder($criteria, 10));
    }

    public function test_find_returns_repository_result(): void
    {
        $flight = new Flight(['flight_number' => 'AC123']);

        $this->repository->shouldReceive('find')->once()->with(3)->andReturn($flight);

        $this->assertSame($flight, $this->service->find(3));
    }

    public function test_create_returns_repository_result(): void
    {
        $payload = ['flight_number' => 'AC123'];
        $flight = new Flight($payload);

        $this->repository->shouldReceive('create')->once()->with($payload)->andReturn($flight);

        $this->assertSame($flight, $this->service->create($payload));
    }

    public function test_update_returns_repository_result(): void
    {
        $payload = ['flight_number' => 'AC456'];
        $flight = new Flight($payload);

        $this->repository->shouldReceive('update')->once()->with(11, $payload)->andReturn($flight);

        $this->assertSame($flight, $this->service->update(11, $payload));
    }

    public function test_delete_delegates_to_repository(): void
    {
        $this->repository->shouldReceive('delete')->once()->with(12);

        $this->service->delete(12);

        $this->assertTrue(true);
    }

    public function test_get_with_relations_returns_repository_result(): void
    {
        $flight = new Flight(['flight_number' => 'AC123']);

        $this->repository->shouldReceive('findWithRelations')->once()->with($flight)->andReturn($flight);

        $this->assertSame($flight, $this->service->getWithRelations($flight));
    }

    public function test_count_returns_repository_count(): void
    {
        $this->repository->shouldReceive('count')->once()->andReturn(27);

        $this->assertSame(27, $this->service->count());
    }
}
