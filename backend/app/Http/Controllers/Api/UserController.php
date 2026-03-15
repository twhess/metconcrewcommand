<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SchedulingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected SchedulingService $schedulingService;

    public function __construct(SchedulingService $schedulingService)
    {
        $this->schedulingService = $schedulingService;
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $query = User::with('roles');

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('is_available')) {
            $query->where('is_available', $request->boolean('is_available'));
        }

        $users = $query->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:users',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'preferred_name' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'is_available' => 'boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['created_by'] = auth()->id();

        // Auto-generate name field from first_name and last_name if not provided
        if (empty($validated['name'])) {
            $validated['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);
        }

        $user = User::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user->load('roles'),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::with(['roles', 'vacations'])->findOrFail($id);

        $this->authorize('view', $user);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $this->authorize('update', $user);

        $validated = $request->validate([
            'username' => 'sometimes|required|string|max:255|unique:users,username,' . $id,
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'preferred_name' => 'nullable|string|max:255',
            'name' => 'sometimes|nullable|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|nullable|string|min:8',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'is_available' => 'boolean',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Auto-generate name field from first_name and last_name if both are provided
        if (isset($validated['first_name']) && isset($validated['last_name'])) {
            $validated['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);
        }

        $validated['updated_by'] = auth()->id();

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user->load('roles'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $this->authorize('delete', $user);

        // Prevent deletion of own account
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete your own account',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }

    public function assignRoles(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $this->authorize('assignRoles', $user);

        $validated = $request->validate([
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $user->roles()->sync($validated['role_ids']);

        return response()->json([
            'success' => true,
            'message' => 'Roles assigned successfully',
            'data' => $user->load('roles'),
        ]);
    }

    public function availability(string $date): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $availableEmployees = $this->schedulingService->getAvailableEmployees($date);

        return response()->json([
            'success' => true,
            'date' => $date,
            'data' => $availableEmployees,
        ]);
    }
}
