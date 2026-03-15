<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Yard;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class YardController extends Controller
{
    /**
     * Display a listing of yards
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Yard::class);

        $query = Yard::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('yard_type')) {
            $query->where('yard_type', $request->input('yard_type'));
        }

        $yards = $query->with(['createdBy', 'updatedBy'])->get();

        return response()->json([
            'success' => true,
            'data' => $yards,
        ]);
    }

    /**
     * Store a newly created yard
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Yard::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'yard_type' => 'required|in:main_yard,satellite_yard,shop,storage',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:2',
            'zip' => 'nullable|string|max:10',
            'gps_latitude' => 'required|numeric|between:-90,90',
            'gps_longitude' => 'required|numeric|between:-180,180',
            'gps_radius_feet' => 'nullable|integer|min:1',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:255',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        $yard = Yard::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Yard created successfully',
            'data' => $yard->load(['createdBy']),
        ], 201);
    }

    /**
     * Display the specified yard
     */
    public function show(int $id): JsonResponse
    {
        $yard = Yard::with(['createdBy', 'updatedBy'])->findOrFail($id);

        $this->authorize('view', $yard);

        return response()->json([
            'success' => true,
            'data' => $yard,
        ]);
    }

    /**
     * Update the specified yard
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $yard = Yard::findOrFail($id);

        $this->authorize('update', $yard);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'yard_type' => 'sometimes|required|in:main_yard,satellite_yard,shop,storage',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:2',
            'zip' => 'nullable|string|max:10',
            'gps_latitude' => 'sometimes|required|numeric|between:-90,90',
            'gps_longitude' => 'sometimes|required|numeric|between:-180,180',
            'gps_radius_feet' => 'nullable|integer|min:1',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:255',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();

        $yard->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Yard updated successfully',
            'data' => $yard->load(['updatedBy']),
        ]);
    }

    /**
     * Remove the specified yard
     */
    public function destroy(int $id): JsonResponse
    {
        $yard = Yard::findOrFail($id);

        $this->authorize('delete', $yard);

        $yard->delete();

        return response()->json([
            'success' => true,
            'message' => 'Yard deleted successfully',
        ]);
    }
}
