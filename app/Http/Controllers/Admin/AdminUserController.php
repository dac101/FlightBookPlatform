<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'role', 'sort']);

        return Inertia::render('Admin/Users/Index', [
            'users' => $this->userService->list(15, $filters),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->userService->create($request->validated());

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(int $user): Response
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => $this->userService->find($user),
        ]);
    }

    public function update(UpdateUserRequest $request, int $user): RedirectResponse
    {
        $this->userService->update($user, $request->validated());

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, int $user): RedirectResponse
    {
        if ($request->user()->id === $user) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot delete your own account.');
        }

        $this->userService->delete($user);

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }
}
