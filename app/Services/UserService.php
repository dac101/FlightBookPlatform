<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    /**
     * @param  array{search?: string, role?: string, sort?: string}  $filters
     */
    public function list(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->userRepository->paginate($perPage, $filters);
    }

    public function find(int $id): User
    {
        return $this->userRepository->find($id);
    }

    public function create(array $data): User
    {
        return $this->userRepository->create($data);
    }

    public function update(int $id, array $data): User
    {
        return $this->userRepository->update($id, $data);
    }

    public function delete(int $id): void
    {
        $this->userRepository->delete($id);
    }

    public function count(): int
    {
        return $this->userRepository->count();
    }
}
