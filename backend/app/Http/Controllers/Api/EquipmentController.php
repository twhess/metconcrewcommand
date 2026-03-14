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
        $this->authorize('viewAny', Equipment::class);

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
        $this->authorize('create', Equipment::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'equipment_number' => 'nullable|string|max:255|unique:equipment',
            'qr_code' => 'required|string|max:255|unique:equipment',
            'type' => 'required|in:trackable,non_trackable',
            'category' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,maintenance,in_transit',
            'current_location_type' => 'nullable|string|max:255',
            'current_location_id' => 'nullable|integer',
            'has_hour_meter' => 'nullable|boolean',
            'current_hours' => 'nullable|numeric|min:0',
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

        $this->authorize('view', $equipment);

        return response()->json([
            'success' => true,
            'data' => $equipment,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $equipment = Equipment::findOrFail($id);

        $this->authorize('update', $equipment);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'equipment_number' => 'nullable|string|max:255|unique:equipment,equipment_number,' . $id,
            'type' => 'sometimes|required|in:trackable,non_trackable',
            'category' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,maintenance,in_transit',
            'has_hour_meter' => 'nullable|boolean',
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

        $this->authorize('delete', $equipment);

        $equipment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Equipment deleted successfully',
        ]);
    }

    public function move(Request $request, int $id): JsonResponse
    {
        $equipment = Equipment::findOrFail($id);

        $this->authorize('move', $equipment);

        $validated = $request->validate([
            'location_type' => 'required|in:project,yard,shop,location,in_transit',
            'location_id' => 'nullable|integer',
            'movement_type' => 'required|in:pickup,dropoff,transfer,return_to_yard',
            'hours_reading' => 'nullable|numeric|min:0',
            'gps_latitude' => 'nullable|numeric|between:-90,90',
            'gps_longitude' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string',
        ]);

        $movement = $this->equipmentService->moveEquipment(
            $id,
            $validated['location_type'],
            $validated['location_id'] ?? null,
            $validated['movement_type'],
            $validated['hours_reading'] ?? null,
            $validated['gps_latitude'] ?? null,
            $validated['gps_longitude'] ?? null,
            false,
            null,
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

    /**
     * Update equipment hour meter reading
     */
    public function updateHours(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'hours_reading' => 'required|numeric|min:0',
        ]);

        $equipment = $this->equipmentService->updateHours($id, $validated['hours_reading']);

        return response()->json([
            'success' => true,
            'message' => 'Hour meter updated successfully',
            'data' => $equipment,
        ]);
    }

    /**
     * Generate QR code for equipment
     */
    public function generateQrCode(int $id): JsonResponse
    {
        $qrCode = $this->equipmentService->generateQrCode($id);

        return response()->json([
            'success' => true,
            'message' => 'QR code generated successfully',
            'data' => [
                'qr_code' => $qrCode,
            ],
        ]);
    }
}
