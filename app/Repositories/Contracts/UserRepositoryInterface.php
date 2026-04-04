<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    /**
     * @param  array{search?: string, role?: string, sort?: string}  $filters
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    public function find(int $id): User;

    public function create(array $data): User;

    public function update(int $id, array $data): User;

    public function delete(int $id): void;

    public function count(): int;
}
