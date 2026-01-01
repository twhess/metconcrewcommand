<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Services\EquipmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EquipmentController extends Controller
{
    protected EquipmentService $equipmentService;

    public function __construct(EquipmentService $equipmentService)
    {
        $this->equipmentService = $equipmentService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Equipment::query();

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $equipment = $query->get();

        return response()->json([
            'success' => true,
            'data' => $equipment,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'equipment_number' => 'nullable|string|max:255|unique:equipment',
            'type' => 'required|in:trackable,non_trackable',
            'category' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,maintenance',
            'current_location_type' => 'nullable|string|max:255',
            'current_location_id' => 'nullable|integer',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        $equipment = Equipment::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Equipment created successfully',
            'data' => $equipment,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $equipment = Equipment::with(['movements'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $equipment,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $equipment = Equipment::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'equipment_number' => 'nullable|string|max:255|unique:equipment,equipment_number,' . $id,
            'type' => 'sometimes|required|in:trackable,non_trackable',
            'category' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,maintenance',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();

        $equipment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Equipment updated successfully',
            'data' => $equipment,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $equipment = Equipment::findOrFail($id);
        $equipment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Equipment deleted successfully',
        ]);
    }

    public function move(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'location_type' => 'required|in:project,location,available',
            'location_id' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        $movement = $this->equipmentService->moveEquipment(
            $id,
            $validated['location_type'],
            $validated['location_id'] ?? null,
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Equipment moved successfully',
            'data' => $movement,
        ]);
    }

    public function availableForDate(string $date, Request $request): JsonResponse
    {
        $availableEquipment = [];
        $equipment = Equipment::where('status', 'active')->get();

        foreach ($equipment as $item) {
            $isAvailable = !\App\Models\EquipmentAssignment::whereHas('schedule', function ($query) use ($date) {
                $query->where('date', $date);
            })->where('equipment_id', $item->id)->exists();

            if ($isAvailable) {
                $availableEquipment[] = $item;
            }
        }

        return response()->json([
            'success' => true,
            'date' => $date,
            'data' => $availableEquipment,
        ]);
    }
}
