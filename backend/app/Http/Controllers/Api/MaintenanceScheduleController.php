<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceScheduleController extends Controller
{
    /**
     * Get all maintenance schedules (with filters)
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MaintenanceSchedule::class);

        $query = MaintenanceSchedule::query()
            ->with(['maintainable', 'assignedUser', 'assignedVendor']);

        // Filter by maintainable type
        if ($request->has('maintainable_type')) {
            $query->where('maintainable_type', $request->maintainable_type);
        }

        // Filter by maintainable ID
        if ($request->has('maintainable_id')) {
            $query->where('maintainable_id', $request->maintainable_id);
        }

        // Filter by maintenance type
        if ($request->has('maintenance_type')) {
            $query->where('maintenance_type', $request->maintenance_type);
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by overdue status
        if ($request->has('is_overdue')) {
            $query->where('is_overdue', $request->boolean('is_overdue'));
        }

        // Filter by frequency type
        if ($request->has('frequency_type')) {
            $query->where('frequency_type', $request->frequency_type);
        }

        $schedules = $query->orderBy('next_due_date')->paginate(50);

        return response()->json($schedules);
    }

    /**
     * Create a new maintenance schedule
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', MaintenanceSchedule::class);

        $validated = $request->validate([
            'maintainable_type' => 'required|string|in:App\\Models\\Equipment,App\\Models\\Vehicle',
            'maintainable_id' => 'required|integer',
            'maintenance_type' => 'required|string|max:100',
            'description' => 'nullable|string',
            'category' => 'required|in:preventive,inspection,seasonal',
            'frequency_type' => 'required|in:calendar,usage,hybrid',

            // Calendar-based
            'frequency_days' => 'nullable|integer|min:1',
            'next_due_date' => 'nullable|date',

            // Usage-based
            'frequency_miles' => 'nullable|numeric|min:0',
            'frequency_hours' => 'nullable|numeric|min:0',
            'next_due_odometer' => 'nullable|numeric|min:0',
            'next_due_hours' => 'nullable|numeric|min:0',

            // Notifications
            'notify_days_before' => 'nullable|integer|min:0',
            'notify_miles_before' => 'nullable|numeric|min:0',
            'notify_hours_before' => 'nullable|numeric|min:0',

            // Assignment
            'assigned_to_type' => 'required|in:internal,vendor',
            'assigned_user_id' => 'nullable|exists:users,id',
            'assigned_vendor_id' => 'nullable|exists:companies,id',

            // Cost estimates
            'estimated_cost' => 'nullable|numeric|min:0',
            'estimated_labor_hours' => 'nullable|numeric|min:0',

            // Status
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $schedule = MaintenanceSchedule::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance schedule created successfully',
            'data' => $schedule->load(['assignedUser', 'assignedVendor']),
        ], 201);
    }

    /**
     * Get a single maintenance schedule
     */
    public function show(int $id): JsonResponse
    {
        $schedule = MaintenanceSchedule::with([
            'maintainable',
            'assignedUser',
            'assignedVendor',
            'createdBy',
            'updatedBy'
        ])->findOrFail($id);

        $this->authorize('view', $schedule);

        return response()->json($schedule);
    }

    /**
     * Update a maintenance schedule
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'maintenance_type' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'category' => 'sometimes|in:preventive,inspection,seasonal',
            'frequency_type' => 'sometimes|in:calendar,usage,hybrid',

            // Calendar-based
            'frequency_days' => 'nullable|integer|min:1',
            'next_due_date' => 'nullable|date',

            // Usage-based
            'frequency_miles' => 'nullable|numeric|min:0',
            'frequency_hours' => 'nullable|numeric|min:0',
            'next_due_odometer' => 'nullable|numeric|min:0',
            'next_due_hours' => 'nullable|numeric|min:0',

            // Notifications
            'notify_days_before' => 'nullable|integer|min:0',
            'notify_miles_before' => 'nullable|numeric|min:0',
            'notify_hours_before' => 'nullable|numeric|min:0',

            // Assignment
            'assigned_to_type' => 'sometimes|in:internal,vendor',
            'assigned_user_id' => 'nullable|exists:users,id',
            'assigned_vendor_id' => 'nullable|exists:companies,id',

            // Cost estimates
            'estimated_cost' => 'nullable|numeric|min:0',
            'estimated_labor_hours' => 'nullable|numeric|min:0',

            // Status
            'is_active' => 'nullable|boolean',
            'is_overdue' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $schedule = MaintenanceSchedule::findOrFail($id);

        $this->authorize('update', $schedule);

        $validated['updated_by'] = auth()->id();

        $schedule->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance schedule updated successfully',
            'data' => $schedule->load(['assignedUser', 'assignedVendor']),
        ]);
    }

    /**
     * Delete a maintenance schedule (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        $schedule = MaintenanceSchedule::findOrFail($id);

        $this->authorize('delete', $schedule);

        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Maintenance schedule deleted successfully',
        ]);
    }

    /**
     * Activate a maintenance schedule
     */
    public function activate(int $id): JsonResponse
    {
        $schedule = MaintenanceSchedule::findOrFail($id);

        $this->authorize('update', $schedule);

        $schedule->update([
            'is_active' => true,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance schedule activated',
            'data' => $schedule,
        ]);
    }

    /**
     * Deactivate a maintenance schedule
     */
    public function deactivate(int $id): JsonResponse
    {
        $schedule = MaintenanceSchedule::findOrFail($id);

        $this->authorize('update', $schedule);

        $schedule->update([
            'is_active' => false,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance schedule deactivated',
            'data' => $schedule,
        ]);
    }

    /**
     * Get all active schedules
     */
    public function active(): JsonResponse
    {
        $this->authorize('viewAny', MaintenanceSchedule::class);

        $schedules = MaintenanceSchedule::where('is_active', true)
            ->with(['maintainable', 'assignedUser', 'assignedVendor'])
            ->orderBy('next_due_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $schedules,
        ]);
    }

    /**
     * Get all overdue schedules
     */
    public function overdue(): JsonResponse
    {
        $this->authorize('viewAny', MaintenanceSchedule::class);

        $schedules = MaintenanceSchedule::where('is_active', true)
            ->where('is_overdue', true)
            ->with(['maintainable', 'assignedUser', 'assignedVendor'])
            ->orderBy('next_due_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $schedules,
            'count' => $schedules->count(),
        ]);
    }

    /**
     * Get schedules for a specific item
     */
    public function forItem(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MaintenanceSchedule::class);

        $validated = $request->validate([
            'maintainable_type' => 'required|string|in:App\\Models\\Equipment,App\\Models\\Vehicle',
            'maintainable_id' => 'required|integer',
        ]);

        $schedules = MaintenanceSchedule::where('maintainable_type', $validated['maintainable_type'])
            ->where('maintainable_id', $validated['maintainable_id'])
            ->with(['assignedUser', 'assignedVendor'])
            ->orderBy('next_due_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $schedules,
        ]);
    }
}
