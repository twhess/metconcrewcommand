<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Contact::with(['company', 'roles', 'contactRoles.location', 'createdBy', 'updatedBy']);

        if ($request->has('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $contacts = $query->orderBy('last_name')->orderBy('first_name')->get();

        return response()->json([
            'success' => true,
            'data' => $contacts,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone_mobile' => 'nullable|string|max:20',
            'phone_work' => 'nullable|string|max:20',
            'phone_other' => 'nullable|string|max:20',
            'preferred_contact_method' => 'nullable|string|in:email,mobile,work,other',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'roles' => 'nullable|array',
            'roles.*.role_id' => 'required|exists:roles,id',
            'roles.*.location_id' => 'nullable|exists:company_locations,id',
            'roles.*.is_primary_for_role' => 'boolean',
            'roles.*.notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        $rolesData = $validated['roles'] ?? [];
        unset($validated['roles']);

        $contact = Contact::create($validated);

        // Attach roles if provided
        if (!empty($rolesData)) {
            foreach ($rolesData as $roleData) {
                $contact->contactRoles()->create([
                    'role_id' => $roleData['role_id'],
                    'location_id' => $roleData['location_id'] ?? null,
                    'is_primary_for_role' => $roleData['is_primary_for_role'] ?? false,
                    'notes' => $roleData['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Contact created successfully',
            'data' => $contact->load(['company', 'roles', 'contactRoles.location']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $contact = Contact::with(['company', 'roles', 'contactRoles.location', 'contactRoles.role', 'createdBy', 'updatedBy'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $contact,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $contact = Contact::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'sometimes|required|exists:companies,id',
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'title' => 'nullable|string|max:255',
            'email' => 'sometimes|required|email|max:255',
            'phone_mobile' => 'nullable|string|max:20',
            'phone_work' => 'nullable|string|max:20',
            'phone_other' => 'nullable|string|max:20',
            'preferred_contact_method' => 'nullable|string|in:email,mobile,work,other',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'roles' => 'nullable|array',
            'roles.*.role_id' => 'required|exists:roles,id',
            'roles.*.location_id' => 'nullable|exists:company_locations,id',
            'roles.*.is_primary_for_role' => 'boolean',
            'roles.*.notes' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();

        $rolesData = $validated['roles'] ?? null;
        unset($validated['roles']);

        $contact->update($validated);

        // Update roles if provided
        if ($rolesData !== null) {
            // Delete existing roles and recreate
            $contact->contactRoles()->delete();

            foreach ($rolesData as $roleData) {
                $contact->contactRoles()->create([
                    'role_id' => $roleData['role_id'],
                    'location_id' => $roleData['location_id'] ?? null,
                    'is_primary_for_role' => $roleData['is_primary_for_role'] ?? false,
                    'notes' => $roleData['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Contact updated successfully',
            'data' => $contact->load(['company', 'roles', 'contactRoles.location', 'contactRoles.role']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $contact = Contact::findOrFail($id);
        $contact->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contact deleted successfully',
        ]);
    }
}
