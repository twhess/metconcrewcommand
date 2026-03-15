<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Project::class);

        $query = Project::with(['company', 'specificationTemplate']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }

        if ($request->has('project_type')) {
            $query->where('project_type', $request->input('project_type'));
        }

        $projects = $query->get();

        // Add completion percentage to each project
        $projects->each(function ($project) {
            $project->completion_percentage = $project->getCompletionPercentage();
        });

        return response()->json([
            'success' => true,
            'data' => $projects,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'project_number' => 'nullable|string|max:255|unique:projects',
            'status' => 'nullable|in:planning,active,on_hold,completed,cancelled',
            'project_type' => 'nullable|string|max:100',
            'specification_template_id' => 'nullable|exists:specification_templates,id',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|size:2',
            'zip' => 'nullable|string|max:10',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        // If specification_template_id is provided, use ProjectService to create with template
        if (isset($validated['specification_template_id'])) {
            $service = new ProjectService();
            $templateId = $validated['specification_template_id'];
            unset($validated['specification_template_id']); // Remove from validated data as service handles it

            $project = $service->createProjectFromTemplate($validated, $templateId);
        } else {
            $project = Project::create($validated);
        }

        return response()->json([
            'success' => true,
            'message' => 'Project created successfully',
            'data' => $project->load(['company', 'specificationTemplate']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $project = Project::with([
            'company',
            'specificationTemplate',
            'specifications',
            'phases',
            'projectContacts.contact.company',
            'projectVendors.company',
        ])->findOrFail($id);

        $this->authorize('view', $project);

        $project->completion_percentage = $project->getCompletionPercentage();

        return response()->json([
            'success' => true,
            'data' => $project,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        $this->authorize('update', $project);

        $validated = $request->validate([
            'company_id' => 'sometimes|required|exists:companies,id',
            'name' => 'sometimes|required|string|max:255',
            'project_number' => 'nullable|string|max:255|unique:projects,project_number,' . $id,
            'status' => 'nullable|in:planning,active,on_hold,completed,cancelled',
            'project_type' => 'nullable|string|max:100',
            'specification_template_id' => 'nullable|exists:specification_templates,id',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|size:2',
            'zip' => 'nullable|string|max:10',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();

        $project->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully',
            'data' => $project->load(['company', 'specificationTemplate']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        $this->authorize('delete', $project);

        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully',
        ]);
    }

    /**
     * Get comprehensive project summary/dashboard.
     */
    public function summary(int $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        $this->authorize('view', $project);

        $service = new ProjectService();
        $dashboard = $service->getProjectDashboard($id);

        return response()->json([
            'success' => true,
            'data' => $dashboard,
        ]);
    }

    /**
     * Mark a project as complete.
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        $this->authorize('update', $project);

        $service = new ProjectService();

        try {
            $project = $service->markProjectComplete($id);

            return response()->json([
                'success' => true,
                'message' => 'Project marked as complete successfully',
                'data' => $project->load(['company', 'specificationTemplate']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Duplicate a project.
     */
    public function duplicate(Request $request, int $id): JsonResponse
    {
        $this->authorize('create', Project::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'project_number' => 'required|string|max:255|unique:projects',
        ]);

        $service = new ProjectService();

        try {
            $newProject = $service->duplicateProject(
                $id,
                $validated['name'],
                $validated['project_number']
            );

            return response()->json([
                'success' => true,
                'message' => 'Project duplicated successfully',
                'data' => $newProject->load(['company', 'specificationTemplate']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
