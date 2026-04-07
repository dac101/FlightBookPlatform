<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\UserService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private UserRepositoryInterface $repository;

    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(UserRepositoryInterface::class);
        $this->service = new UserService($this->repository);
    }

    public function test_list_delegates_to_repository_paginate(): void
    {
        $filters = ['search' => 'john', 'role' => 'admin', 'sort' => 'recent'];
        $paginator = new LengthAwarePaginator([], 0, 15, 1);

        $this->repository->shouldReceive('paginate')->once()->with(15, $filters)->andReturn($paginator);

        $this->assertSame($paginator, $this->service->list(15, $filters));
    }

    public function test_find_returns_repository_result(): void
    {
        $user = new User(['name' => 'John Doe', 'email' => 'john@example.com']);

        $this->repository->shouldReceive('find')->once()->with(4)->andReturn($user);

        $this->assertSame($user, $this->service->find(4));
    }

    public function test_create_returns_repository_result(): void
    {
        $payload = ['name' => 'John Doe', 'email' => 'john@example.com'];
        $user = new User($payload);

        $this->repository->shouldReceive('create')->once()->with($payload)->andReturn($user);

        $this->assertSame($user, $this->service->create($payload));
    }

    public function test_update_returns_repository_result(): void
    {
        $payload = ['name' => 'Jane Doe'];
        $user = new User(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

        $this->repository->shouldReceive('update')->once()->with(5, $payload)->andReturn($user);

        $this->assertSame($user, $this->service->update(5, $payload));
    }

    public function test_delete_delegates_to_repository(): void
    {
        $this->repository->shouldReceive('delete')->once()->with(9);

        $this->service->delete(9);

        $this->assertTrue(true);
    }

    public function test_count_returns_repository_count(): void
    {
        $this->repository->shouldReceive('count')->once()->andReturn(31);

        $this->assertSame(31, $this->service->count());
    }
}
