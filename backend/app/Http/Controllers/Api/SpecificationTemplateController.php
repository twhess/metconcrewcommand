<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\SpecificationTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpecificationTemplateController extends Controller
{
    /**
     * Display a listing of specification templates.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SpecificationTemplate::class);

        $query = SpecificationTemplate::query()->with('items');

        // Filter by project type
        if ($request->has('project_type')) {
            $query->byProjectType($request->project_type);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $templates = $query->orderBy('name')->paginate(50);

        return response()->json($templates);
    }

    /**
     * Store a newly created specification template.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', SpecificationTemplate::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:specification_templates,slug',
            'project_type' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'items' => 'nullable|array',
            'items.*.category' => 'required|string|max:50',
            'items.*.requirement_name' => 'required|string|max:255',
            'items.*.requirement_description' => 'nullable|string',
            'items.*.is_required' => 'boolean',
            'items.*.default_value' => 'nullable',
            'items.*.sort_order' => 'integer',
        ]);

        $template = SpecificationTemplate::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'project_type' => $validated['project_type'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        // Create template items
        if (isset($validated['items'])) {
            foreach ($validated['items'] as $itemData) {
                $template->items()->create([
                    'category' => $itemData['category'],
                    'requirement_name' => $itemData['requirement_name'],
                    'requirement_description' => $itemData['requirement_description'] ?? null,
                    'is_required' => $itemData['is_required'] ?? true,
                    'default_value' => $itemData['default_value'] ?? null,
                    'sort_order' => $itemData['sort_order'] ?? 0,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Specification template created successfully',
            'data' => $template->load('items')
        ], 201);
    }

    /**
     * Display the specified specification template.
     */
    public function show(int $id): JsonResponse
    {
        $template = SpecificationTemplate::with('items')->findOrFail($id);

        $this->authorize('view', $template);

        return response()->json([
            'success' => true,
            'data' => $template
        ]);
    }

    /**
     * Update the specified specification template.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $template = SpecificationTemplate::findOrFail($id);

        $this->authorize('update', $template);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:specification_templates,slug,' . $id,
            'project_type' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['updated_by'] = auth()->id();
        $template->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Specification template updated successfully',
            'data' => $template->load('items')
        ]);
    }

    /**
     * Remove the specified specification template.
     */
    public function destroy(int $id): JsonResponse
    {
        $template = SpecificationTemplate::findOrFail($id);

        $this->authorize('delete', $template);

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Specification template deleted successfully'
        ]);
    }

    /**
     * Duplicate a specification template.
     */
    public function duplicate(Request $request, int $id): JsonResponse
    {
        $template = SpecificationTemplate::with('items')->findOrFail($id);

        $this->authorize('create', SpecificationTemplate::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:specification_templates,slug',
        ]);

        $duplicate = $template->duplicate($validated['name'], $validated['slug']);

        return response()->json([
            'success' => true,
            'message' => 'Specification template duplicated successfully',
            'data' => $duplicate
        ], 201);
    }

    /**
     * Apply a template to a project.
     */
    public function applyToProject(Request $request, int $id): JsonResponse
    {
        $template = SpecificationTemplate::with('items')->findOrFail($id);

        $this->authorize('update', $template);

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
        ]);

        $project = Project::findOrFail($validated['project_id']);

        // Apply template to project
        $template->applyToProject($project);

        // Update project's template reference
        $project->update([
            'specification_template_id' => $template->id,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template applied to project successfully',
            'data' => [
                'project' => $project->load('specifications'),
                'specifications_created' => $template->items->count()
            ]
        ]);
    }
}
