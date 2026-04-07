<?php

namespace Tests\Unit\Services;

use App\Models\Trip;
use App\Models\User;
use App\Repositories\Contracts\TripRepositoryInterface;
use App\Services\TripService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class TripServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private TripRepositoryInterface $repository;

    private TripService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(TripRepositoryInterface::class);
        $this->service = new TripService($this->repository);
    }

    public function test_list_all_delegates_to_repository(): void
    {
        $filters = ['search' => 'Montreal', 'status' => 'pending'];
        $paginator = new LengthAwarePaginator([], 0, 15, 1);

        $this->repository->shouldReceive('paginateAll')->once()->with(15, $filters)->andReturn($paginator);

        $this->assertSame($paginator, $this->service->listAll(15, $filters));
    }

    public function test_list_for_user_delegates_to_repository(): void
    {
        $user = new User(['name' => 'John Doe']);
        $paginator = new LengthAwarePaginator([], 0, 20, 1);

        $this->repository->shouldReceive('paginateForUser')->once()->with($user, 20)->andReturn($paginator);

        $this->assertSame($paginator, $this->service->listForUser($user, 20));
    }

    public function test_create_returns_repository_result(): void
    {
        $user = new User(['name' => 'John Doe']);
        $payload = ['trip_name' => 'Trip'];
        $trip = Mockery::mock(Trip::class);

        $this->repository->shouldReceive('create')->once()->with($user, $payload)->andReturn($trip);

        $this->assertSame($trip, $this->service->create($user, $payload));
    }

    public function test_book_recalculates_and_returns_trip_with_segments(): void
    {
        $user = new User(['name' => 'John Doe']);
        $trip = Mockery::mock(Trip::class);
        $freshTrip = Mockery::mock(Trip::class);
        $segments = [['flight_id' => 1, 'segment_order' => 1, 'departure_date' => now()->addDay()->toDateString(), 'segment_type' => 'outbound']];

        $this->repository->shouldReceive('createWithSegments')->once()->with($user, ['trip_type' => 'one_way'], $segments)->andReturn($trip);
        $trip->shouldReceive('recalculateTotal')->once();
        $trip->shouldReceive('fresh')->once()->andReturn($freshTrip);
        $this->repository->shouldReceive('findWithSegments')->once()->with($freshTrip)->andReturn($freshTrip);

        $this->assertSame($freshTrip, $this->service->book($user, ['trip_type' => 'one_way'], $segments));
    }

    public function test_get_with_segments_returns_repository_result(): void
    {
        $trip = Mockery::mock(Trip::class);

        $this->repository->shouldReceive('findWithSegments')->once()->with($trip)->andReturn($trip);

        $this->assertSame($trip, $this->service->getWithSegments($trip));
    }

    public function test_find_returns_repository_result(): void
    {
        $trip = Mockery::mock(Trip::class);

        $this->repository->shouldReceive('find')->once()->with(7)->andReturn($trip);

        $this->assertSame($trip, $this->service->find(7));
    }

    public function test_update_recalculates_and_returns_fresh_trip(): void
    {
        $trip = Mockery::mock(Trip::class);

        $this->repository->shouldReceive('update')->once()->with($trip, ['status' => 'confirmed'])->andReturn($trip);
        $trip->shouldReceive('recalculateTotal')->once();
        $trip->shouldReceive('fresh')->once()->andReturn($trip);

        $this->assertSame($trip, $this->service->update($trip, ['status' => 'confirmed']));
    }

    public function test_append_segment_recalculates_and_returns_trip_with_segments(): void
    {
        $trip = Mockery::mock(Trip::class);
        $freshTrip = Mockery::mock(Trip::class);

        $this->repository->shouldReceive('appendSegment')->once()->with($trip, ['flight_id' => 3], ['status' => 'pending'])->andReturn($trip);
        $trip->shouldReceive('recalculateTotal')->once();
        $trip->shouldReceive('fresh')->once()->andReturn($freshTrip);
        $this->repository->shouldReceive('findWithSegments')->once()->with($freshTrip)->andReturn($freshTrip);

        $this->assertSame($freshTrip, $this->service->appendSegment($trip, ['flight_id' => 3], ['status' => 'pending']));
    }

    public function test_replace_segment_recalculates_and_returns_trip_with_segments(): void
    {
        $trip = Mockery::mock(Trip::class);
        $freshTrip = Mockery::mock(Trip::class);

        $this->repository->shouldReceive('replaceSegments')->once()->with($trip, ['flight_id' => 4], ['departure_date' => now()->addDay()->toDateString()])->andReturn($trip);
        $trip->shouldReceive('recalculateTotal')->once();
        $trip->shouldReceive('fresh')->once()->andReturn($freshTrip);
        $this->repository->shouldReceive('findWithSegments')->once()->with($freshTrip)->andReturn($freshTrip);

        $this->assertSame($freshTrip, $this->service->replaceSegment($trip, ['flight_id' => 4], ['departure_date' => now()->addDay()->toDateString()]));
    }

    public function test_rebuild_with_segments_recalculates_and_returns_trip_with_segments(): void
    {
        $trip = Mockery::mock(Trip::class);
        $freshTrip = Mockery::mock(Trip::class);
        $segments = [['flight_id' => 5, 'segment_order' => 1, 'departure_date' => now()->addDay()->toDateString(), 'segment_type' => 'outbound']];

        $this->repository->shouldReceive('rebuildWithSegments')->once()->with($trip, ['trip_type' => 'one_way'], $segments)->andReturn($trip);
        $trip->shouldReceive('recalculateTotal')->once();
        $trip->shouldReceive('fresh')->once()->andReturn($freshTrip);
        $this->repository->shouldReceive('findWithSegments')->once()->with($freshTrip)->andReturn($freshTrip);

        $this->assertSame($freshTrip, $this->service->rebuildWithSegments($trip, ['trip_type' => 'one_way'], $segments));
    }

    public function test_delete_delegates_to_repository(): void
    {
        $trip = Mockery::mock(Trip::class);

        $this->repository->shouldReceive('delete')->once()->with($trip);

        $this->service->delete($trip);

        $this->assertTrue(true);
    }

    public function test_count_returns_repository_count(): void
    {
        $this->repository->shouldReceive('count')->once()->andReturn(13);

        $this->assertSame(13, $this->service->count());
    }
}
