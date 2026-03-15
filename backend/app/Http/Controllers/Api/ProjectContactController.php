<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Project;
use App\Models\ProjectContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectContactController extends Controller
{
    /**
     * Display contacts for a project.
     */
    public function index(Request $request, int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('view', $project);

        $query = $project->projectContacts()->with(['contact.company']);

        // Filter by role
        if ($request->has('role')) {
            $query->byRole($request->role);
        }

        $projectContacts = $query->get();

        // Group by role for easier frontend consumption
        $grouped = $projectContacts->groupBy('role');

        return response()->json([
            'success' => true,
            'data' => [
                'project_contacts' => $projectContacts,
                'grouped' => $grouped,
                'summary' => [
                    'total' => $projectContacts->count(),
                    'roles' => $grouped->keys()->toArray(),
                    'primary_contacts' => $projectContacts->where('is_primary', true)->count(),
                ]
            ]
        ]);
    }

    /**
     * Assign a contact to a project.
     */
    public function store(Request $request, int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('update', $project);

        $validated = $request->validate([
            'contact_id' => 'required|integer|exists:contacts,id',
            'role' => 'required|string|max:100',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        // Verify contact exists
        Contact::findOrFail($validated['contact_id']);

        // Check if this contact is already assigned this role on this project
        $existing = $project->projectContacts()
            ->where('contact_id', $validated['contact_id'])
            ->where('role', $validated['role'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'This contact is already assigned this role on this project'
            ], 422);
        }

        // If setting as primary, unset other primary contacts for this role
        if ($validated['is_primary'] ?? false) {
            $project->projectContacts()
                ->where('role', $validated['role'])
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $projectContact = $project->projectContacts()->create([
            'contact_id' => $validated['contact_id'],
            'role' => $validated['role'],
            'is_primary' => $validated['is_primary'] ?? false,
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contact assigned to project successfully',
            'data' => $projectContact->load('contact.company')
        ], 201);
    }

    /**
     * Display a specific project contact assignment.
     */
    public function show(int $projectId, int $id): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('view', $project);

        $projectContact = $project->projectContacts()->with(['contact.company'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $projectContact
        ]);
    }

    /**
     * Update a project contact assignment.
     */
    public function update(Request $request, int $projectId, int $id): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('update', $project);

        $projectContact = $project->projectContacts()->findOrFail($id);

        $validated = $request->validate([
            'role' => 'sometimes|string|max:100',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        // If changing to primary, unset other primary contacts for this role
        if (isset($validated['is_primary']) && $validated['is_primary']) {
            $role = $validated['role'] ?? $projectContact->role;
            $project->projectContacts()
                ->where('role', $role)
                ->where('id', '!=', $id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $validated['updated_by'] = auth()->id();
        $projectContact->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Contact assignment updated successfully',
            'data' => $projectContact->load('contact.company')
        ]);
    }

    /**
     * Remove a contact from a project.
     */
    public function destroy(int $projectId, int $id): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('update', $project);

        $projectContact = $project->projectContacts()->findOrFail($id);
        $projectContact->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contact removed from project successfully'
        ]);
    }

    /**
     * Get primary contact for a specific role.
     */
    public function primaryByRole(int $projectId, string $role): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('view', $project);

        $primaryContact = $project->projectContacts()
            ->with(['contact.company'])
            ->byRole($role)
            ->primary()
            ->first();

        if (!$primaryContact) {
            return response()->json([
                'success' => false,
                'message' => "No primary contact found for role: {$role}"
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $primaryContact
        ]);
    }
}
