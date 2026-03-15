<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyLocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CompanyLocation::class);

        $query = CompanyLocation::with(['company', 'createdBy', 'updatedBy']);

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('is_primary')) {
            $query->where('is_primary', $request->boolean('is_primary'));
        }

        $locations = $query->orderBy('is_primary', 'desc')
            ->orderBy('location_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $locations,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', CompanyLocation::class);

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'location_name' => 'required|string|max:255',
            'location_type' => 'nullable|string|max:255',
            'is_primary' => 'boolean',
            'address1' => 'nullable|string|max:255',
            'address2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:2',
            'zip' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'hours' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['created_by'] = Auth::id();

        // If this is being set as primary, unset other primary locations for this company
        if (isset($validated['is_primary']) && $validated['is_primary']) {
            CompanyLocation::where('company_id', $validated['company_id'])
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $location = CompanyLocation::create($validated);
        $location->load(['company', 'createdBy']);

        return response()->json([
            'success' => true,
            'data' => $location,
            'message' => 'Location created successfully',
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $location = CompanyLocation::with(['company', 'contactRoles.contact', 'contactRoles.role', 'createdBy', 'updatedBy'])
            ->findOrFail($id);

        $this->authorize('view', $location);

        return response()->json([
            'success' => true,
            'data' => $location,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $location = CompanyLocation::findOrFail($id);

        $this->authorize('update', $location);

        $validated = $request->validate([
            'location_name' => 'sometimes|required|string|max:255',
            'location_type' => 'nullable|string|max:255',
            'is_primary' => 'boolean',
            'address1' => 'nullable|string|max:255',
            'address2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:2',
            'zip' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'hours' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['updated_by'] = Auth::id();

        // If this is being set as primary, unset other primary locations for this company
        if (isset($validated['is_primary']) && $validated['is_primary']) {
            CompanyLocation::where('company_id', $location->company_id)
                ->where('id', '!=', $location->id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $location->update($validated);
        $location->load(['company', 'createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'data' => $location,
            'message' => 'Location updated successfully',
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $location = CompanyLocation::findOrFail($id);

        $this->authorize('delete', $location);

        // Prevent deletion if this is the primary location
        if ($location->is_primary) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete primary location. Please set another location as primary first.',
            ], 422);
        }

        $location->delete();

        return response()->json([
            'success' => true,
            'message' => 'Location deleted successfully',
        ]);
    }
}
