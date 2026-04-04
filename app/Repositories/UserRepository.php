<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserRepository implements UserRepositoryInterface
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        try {
            $query = User::query();

            if (! empty($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('email', 'like', '%'.$filters['search'].'%');
                });
            }

            if (! empty($filters['role'])) {
                $query->where('role', $filters['role']);
            }

            if (! empty($filters['sort']) && $filters['sort'] === 'oldest') {
                $query->orderBy('created_at');
            } else {
                $query->orderByDesc('created_at');
            }

            return $query->paginate($perPage)->withQueryString();
        } catch (Throwable $e) {
            Log::error('UserRepository: error paginating users', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function find(int $id): User
    {
        try {
            return User::findOrFail($id);
        } catch (Throwable $e) {
            Log::error('UserRepository: error finding user', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function create(array $data): User
    {
        try {
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $user = User::create($data);

            Log::info('UserRepository: user created', ['id' => $user->id, 'email' => $user->email]);

            return $user;
        } catch (Throwable $e) {
            Log::error('UserRepository: error creating user', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function update(int $id, array $data): User
    {
        try {
            $user = $this->find($id);

            if (! empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            unset($data['password_confirmation']);

            $user->update($data);

            Log::info('UserRepository: user updated', ['id' => $id]);

            return $user->fresh();
        } catch (Throwable $e) {
            Log::error('UserRepository: error updating user', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        try {
            $user = $this->find($id);
            $user->delete();

            Log::info('UserRepository: user deleted', ['id' => $id]);
        } catch (Throwable $e) {
            Log::error('UserRepository: error deleting user', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function count(): int
    {
        try {
            return User::count();
        } catch (Throwable $e) {
            Log::error('UserRepository: error counting users', ['message' => $e->getMessage()]);

            return 0;
        }
    }
}
