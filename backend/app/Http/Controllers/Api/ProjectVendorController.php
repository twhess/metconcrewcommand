<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectVendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectVendorController extends Controller
{
    /**
     * Display vendors for a project.
     */
    public function index(Request $request, int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $query = $project->projectVendors()->with(['company']);

        // Filter by vendor type
        if ($request->has('vendor_type')) {
            $query->byType($request->vendor_type);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $projectVendors = $query->get();

        // Group by vendor type for easier frontend consumption
        $grouped = $projectVendors->groupBy('vendor_type');

        return response()->json([
            'success' => true,
            'data' => [
                'project_vendors' => $projectVendors,
                'grouped' => $grouped,
                'summary' => [
                    'total' => $projectVendors->count(),
                    'active' => $projectVendors->where('is_active', true)->count(),
                    'vendor_types' => $grouped->keys()->toArray(),
                    'primary_vendors' => $projectVendors->where('is_primary', true)->count(),
                ]
            ]
        ]);
    }

    /**
     * Assign a vendor to a project.
     */
    public function store(Request $request, int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'vendor_type' => 'required|string|max:100',
            'is_primary' => 'boolean',
            'contract_number' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        // Verify company exists and is a vendor
        $company = Company::findOrFail($validated['company_id']);

        // Check if this company is already assigned this vendor type on this project
        $existing = $project->projectVendors()
            ->where('company_id', $validated['company_id'])
            ->where('vendor_type', $validated['vendor_type'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'This company is already assigned as this vendor type on this project'
            ], 422);
        }

        // If setting as primary, unset other primary vendors for this type
        if ($validated['is_primary'] ?? false) {
            $project->projectVendors()
                ->where('vendor_type', $validated['vendor_type'])
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $projectVendor = $project->projectVendors()->create([
            'company_id' => $validated['company_id'],
            'vendor_type' => $validated['vendor_type'],
            'is_primary' => $validated['is_primary'] ?? false,
            'contract_number' => $validated['contract_number'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vendor assigned to project successfully',
            'data' => $projectVendor->load('company')
        ], 201);
    }

    /**
     * Display a specific project vendor assignment.
     */
    public function show(int $projectId, int $id): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $projectVendor = $project->projectVendors()->with(['company'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $projectVendor
        ]);
    }

    /**
     * Update a project vendor assignment.
     */
    public function update(Request $request, int $projectId, int $id): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $projectVendor = $project->projectVendors()->findOrFail($id);

        $validated = $request->validate([
            'vendor_type' => 'sometimes|string|max:100',
            'is_primary' => 'boolean',
            'contract_number' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        // If changing to primary, unset other primary vendors for this type
        if (isset($validated['is_primary']) && $validated['is_primary']) {
            $vendorType = $validated['vendor_type'] ?? $projectVendor->vendor_type;
            $project->projectVendors()
                ->where('vendor_type', $vendorType)
                ->where('id', '!=', $id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $validated['updated_by'] = auth()->id();
        $projectVendor->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vendor assignment updated successfully',
            'data' => $projectVendor->load('company')
        ]);
    }

    /**
     * Remove a vendor from a project.
     */
    public function destroy(int $projectId, int $id): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $projectVendor = $project->projectVendors()->findOrFail($id);
        $projectVendor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vendor removed from project successfully'
        ]);
    }

    /**
     * Get primary vendor for a specific type.
     */
    public function primaryByType(int $projectId, string $type): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $primaryVendor = $project->projectVendors()
            ->with(['company'])
            ->byType($type)
            ->primary()
            ->active()
            ->first();

        if (!$primaryVendor) {
            return response()->json([
                'success' => false,
                'message' => "No primary vendor found for type: {$type}"
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $primaryVendor
        ]);
    }

    /**
     * Deactivate a vendor assignment.
     */
    public function deactivate(int $projectId, int $id): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $projectVendor = $project->projectVendors()->findOrFail($id);

        $projectVendor->update([
            'is_active' => false,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vendor assignment deactivated successfully',
            'data' => $projectVendor->load('company')
        ]);
    }

    /**
     * Activate a vendor assignment.
     */
    public function activate(int $projectId, int $id): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $projectVendor = $project->projectVendors()->findOrFail($id);

        $projectVendor->update([
            'is_active' => true,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vendor assignment activated successfully',
            'data' => $projectVendor->load('company')
        ]);
    }
}
