<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->userService->list(
                (int) $request->integer('per_page', 10),
                $request->only(['search', 'role', 'sort'])
            )
        );
    }

    public function show(int $user): JsonResponse
    {
        return response()->json($this->userService->find($user));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return response()->json([
            'message' => 'User created successfully.',
            'data' => $user,
        ], 201);
    }

    public function update(UpdateUserRequest $request, int $user): JsonResponse
    {
        $updatedUser = $this->userService->update($user, $request->validated());

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => $updatedUser,
        ]);
    }

    public function destroy(Request $request, int $user): JsonResponse
    {
        if ($request->user()->id === $user) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        $this->userService->delete($user);

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }
}
