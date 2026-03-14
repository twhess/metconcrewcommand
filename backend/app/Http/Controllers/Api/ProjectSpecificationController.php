<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectSpecificationController extends Controller
{
    /**
     * Display specifications for a project.
     */
    public function index(Request $request, int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $query = $project->specifications();

        // Filter by category
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // Filter by compliance status
        if ($request->has('compliance_status')) {
            $query->byComplianceStatus($request->compliance_status);
        }

        $specifications = $query->with(['verifiedBy'])->get();

        // Group by category for easier frontend consumption
        $grouped = $specifications->groupBy('category');

        return response()->json([
            'success' => true,
            'data' => [
                'specifications' => $specifications,
                'grouped' => $grouped,
                'summary' => [
                    'total' => $specifications->count(),
                    'required' => $specifications->where('is_required', true)->count(),
                    'compliant' => $specifications->where('compliance_status', 'compliant')->count(),
                    'non_compliant' => $specifications->where('compliance_status', 'non_compliant')->count(),
                    'in_progress' => $specifications->where('compliance_status', 'in_progress')->count(),
                    'not_started' => $specifications->where('compliance_status', 'not_started')->count(),
                ]
            ]
        ]);
    }

    /**
     * Store a new project specification.
     */
    public function store(Request $request, int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $validated = $request->validate([
            'category' => 'required|string|max:50',
            'requirement_name' => 'required|string|max:255',
            'requirement_description' => 'nullable|string',
            'is_required' => 'boolean',
            'value' => 'nullable',
            'compliance_status' => 'in:not_started,in_progress,compliant,non_compliant',
            'compliance_notes' => 'nullable|string',
            'sort_order' => 'integer',
        ]);

        $specification = $project->specifications()->create([
            'category' => $validated['category'],
            'requirement_name' => $validated['requirement_name'],
            'requirement_description' => $validated['requirement_description'] ?? null,
            'is_required' => $validated['is_required'] ?? true,
            'value' => $validated['value'] ?? null,
            'compliance_status' => $validated['compliance_status'] ?? 'not_started',
            'compliance_notes' => $validated['compliance_notes'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Specification created successfully',
            'data' => $specification
        ], 201);
    }

    /**
     * Display a specific specification.
     */
    public function show(int $projectId, int $id): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $specification = $project->specifications()->with(['verifiedBy'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $specification
        ]);
    }

    /**
     * Update a project specification.
     */
    public function update(Request $request, int $projectId, int $id): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $specification = $project->specifications()->findOrFail($id);

        $validated = $request->validate([
            'category' => 'sometimes|string|max:50',
            'requirement_name' => 'sometimes|string|max:255',
            'requirement_description' => 'nullable|string',
            'is_required' => 'boolean',
            'value' => 'nullable',
            'compliance_status' => 'in:not_started,in_progress,compliant,non_compliant',
            'compliance_notes' => 'nullable|string',
            'sort_order' => 'integer',
        ]);

        $validated['updated_by'] = auth()->id();
        $specification->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Specification updated successfully',
            'data' => $specification->load('verifiedBy')
        ]);
    }

    /**
     * Remove a specification.
     */
    public function destroy(int $projectId, int $id): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $specification = $project->specifications()->findOrFail($id);
        $specification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Specification deleted successfully'
        ]);
    }

    /**
     * Mark a specification as verified.
     */
    public function verify(Request $request, int $projectId, int $id): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $specification = $project->specifications()->findOrFail($id);

        $validated = $request->validate([
            'compliance_notes' => 'nullable|string',
        ]);

        $specification->update([
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'compliance_notes' => $validated['compliance_notes'] ?? $specification->compliance_notes,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Specification verified successfully',
            'data' => $specification->load('verifiedBy')
        ]);
    }
}
