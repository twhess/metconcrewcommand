<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectPhase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectPhaseController extends Controller
{
    /**
     * Display phases for a project.
     */
    public function index(Request $request, int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('view', $project);

        $query = $project->phases();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $phases = $query->with(['completedBy'])->get();

        // Calculate overall project completion
        $totalPhases = $phases->count();
        $overallCompletion = $totalPhases > 0
            ? round($phases->sum('completion_percentage') / $totalPhases, 2)
            : 0.0;

        return response()->json([
            'success' => true,
            'data' => [
                'phases' => $phases,
                'summary' => [
                    'total' => $totalPhases,
                    'pending' => $phases->where('status', 'pending')->count(),
                    'in_progress' => $phases->where('status', 'in_progress')->count(),
                    'completed' => $phases->where('status', 'completed')->count(),
                    'on_hold' => $phases->where('status', 'on_hold')->count(),
                    'overall_completion' => $overallCompletion,
                    'total_estimated_hours' => $phases->sum('estimated_hours'),
                    'total_actual_hours' => $phases->sum('actual_hours'),
                ]
            ]
        ]);
    }

    /**
     * Store a new project phase.
     */
    public function store(Request $request, int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'phase_number' => 'required|integer|min:1',
            'status' => 'in:pending,in_progress,completed,on_hold',
            'estimated_start_date' => 'nullable|date',
            'estimated_end_date' => 'nullable|date|after_or_equal:estimated_start_date',
            'actual_start_date' => 'nullable|date',
            'actual_end_date' => 'nullable|date|after_or_equal:actual_start_date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
            'completion_percentage' => 'integer|min:0|max:100',
            'equipment_needs' => 'nullable|array',
            'equipment_needs.*' => 'string',
            'crew_size_estimate' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $phase = $project->phases()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'phase_number' => $validated['phase_number'],
            'status' => $validated['status'] ?? 'pending',
            'estimated_start_date' => $validated['estimated_start_date'] ?? null,
            'estimated_end_date' => $validated['estimated_end_date'] ?? null,
            'actual_start_date' => $validated['actual_start_date'] ?? null,
            'actual_end_date' => $validated['actual_end_date'] ?? null,
            'estimated_hours' => $validated['estimated_hours'] ?? null,
            'actual_hours' => $validated['actual_hours'] ?? null,
            'completion_percentage' => $validated['completion_percentage'] ?? 0,
            'equipment_needs' => $validated['equipment_needs'] ?? null,
            'crew_size_estimate' => $validated['crew_size_estimate'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Phase created successfully',
            'data' => $phase
        ], 201);
    }

    /**
     * Display a specific phase.
     */
    public function show(int $projectId, int $id): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('view', $project);

        $phase = $project->phases()->with(['completedBy'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $phase
        ]);
    }

    /**
     * Update a project phase.
     */
    public function update(Request $request, int $projectId, int $id): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('update', $project);

        $phase = $project->phases()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'phase_number' => 'sometimes|integer|min:1',
            'status' => 'in:pending,in_progress,completed,on_hold',
            'estimated_start_date' => 'nullable|date',
            'estimated_end_date' => 'nullable|date',
            'actual_start_date' => 'nullable|date',
            'actual_end_date' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
            'completion_percentage' => 'integer|min:0|max:100',
            'equipment_needs' => 'nullable|array',
            'equipment_needs.*' => 'string',
            'crew_size_estimate' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();
        $phase->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Phase updated successfully',
            'data' => $phase->load('completedBy')
        ]);
    }

    /**
     * Remove a phase.
     */
    public function destroy(int $projectId, int $id): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('update', $project);

        $phase = $project->phases()->findOrFail($id);
        $phase->delete();

        return response()->json([
            'success' => true,
            'message' => 'Phase deleted successfully'
        ]);
    }

    /**
     * Mark a phase as complete.
     */
    public function complete(Request $request, int $projectId, int $id): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('update', $project);

        $phase = $project->phases()->findOrFail($id);

        $validated = $request->validate([
            'actual_hours' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $phase->update([
            'status' => 'completed',
            'completion_percentage' => 100,
            'actual_end_date' => now(),
            'actual_hours' => $validated['actual_hours'] ?? $phase->actual_hours,
            'notes' => $validated['notes'] ?? $phase->notes,
            'completed_by' => auth()->id(),
            'completed_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        // If actual_start_date not set, set it to now (retroactively)
        if (!$phase->actual_start_date) {
            $phase->update(['actual_start_date' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Phase marked as complete',
            'data' => $phase->load('completedBy')
        ]);
    }

    /**
     * Reorder phases by updating phase_number.
     */
    public function reorder(Request $request, int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('update', $project);

        $validated = $request->validate([
            'phases' => 'required|array|min:1',
            'phases.*.id' => 'required|integer|exists:project_phases,id',
            'phases.*.phase_number' => 'required|integer|min:1',
        ]);

        foreach ($validated['phases'] as $phaseData) {
            $phase = $project->phases()->findOrFail($phaseData['id']);
            $phase->update([
                'phase_number' => $phaseData['phase_number'],
                'updated_by' => auth()->id(),
            ]);
        }

        $phases = $project->phases()->orderBy('phase_number')->get();

        return response()->json([
            'success' => true,
            'message' => 'Phases reordered successfully',
            'data' => $phases
        ]);
    }
}
