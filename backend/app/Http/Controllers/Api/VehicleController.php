<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\VehicleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VehicleController extends Controller
{
    protected VehicleService $vehicleService;

    public function __construct(VehicleService $vehicleService)
    {
        $this->vehicleService = $vehicleService;
    }

    /**
     * Display a listing of vehicles
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Vehicle::class);

        $query = Vehicle::query();

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('vehicle_type')) {
            $query->where('vehicle_type', $request->input('vehicle_type'));
        }

        if ($request->has('assigned_to_user_id')) {
            $query->where('assigned_to_user_id', $request->input('assigned_to_user_id'));
        }

        if ($request->has('fuel_type')) {
            $query->where('fuel_type', $request->input('fuel_type'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('vehicle_number', 'like', "%{$search}%")
                  ->orWhere('vin', 'like', "%{$search}%")
                  ->orWhere('license_plate', 'like', "%{$search}%")
                  ->orWhere('make', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }

        $vehicles = $query->with(['assignedTo', 'latestMovement'])->get();

        return response()->json([
            'success' => true,
            'data' => $vehicles,
        ]);
    }

    /**
     * Store a newly created vehicle
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Vehicle::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'vehicle_number' => 'nullable|string|max:255|unique:vehicles',
            'qr_code' => 'required|string|max:255|unique:vehicles',
            'vin' => 'nullable|string|max:17|unique:vehicles',
            'license_plate' => 'nullable|string|max:20',
            'registration_state' => 'nullable|string|max:2',
            'registration_expiration' => 'nullable|date',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'make' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'vehicle_type' => 'nullable|in:dump_truck,concrete_mixer,pickup,flatbed,lowboy,skid_steer_trailer,utility_van,service_truck',
            'fuel_type' => 'nullable|in:diesel,gasoline,electric,hybrid,propane',
            'tank_capacity_gallons' => 'nullable|numeric|min:0',
            'weight_class' => 'nullable|string|max:20',
            'gvwr_pounds' => 'nullable|integer|min:0',
            'towing_capacity_pounds' => 'nullable|integer|min:0',
            'current_odometer_miles' => 'nullable|numeric|min:0',
            'requires_cdl' => 'nullable|boolean',
            'requires_dot_inspection' => 'nullable|boolean',
            'last_dot_inspection_date' => 'nullable|date',
            'next_dot_inspection_due' => 'nullable|date',
            'insurance_policy_number' => 'nullable|string|max:100',
            'insurance_provider' => 'nullable|string|max:255',
            'insurance_expiration' => 'nullable|date',
            'status' => 'nullable|in:active,inactive,maintenance,out_of_service,in_transit',
            'current_location_type' => 'nullable|string|max:50',
            'current_location_id' => 'nullable|integer',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        $vehicle = Vehicle::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle created successfully',
            'data' => $vehicle->load(['assignedTo']),
        ], 201);
    }

    /**
     * Display the specified vehicle
     */
    public function show(int $id): JsonResponse
    {
        $vehicle = Vehicle::with(['movements.movedByUser', 'assignedTo', 'createdBy', 'updatedBy'])
            ->findOrFail($id);

        $this->authorize('view', $vehicle);

        return response()->json([
            'success' => true,
            'data' => $vehicle,
        ]);
    }

    /**
     * Update the specified vehicle
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);

        $this->authorize('update', $vehicle);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'vehicle_number' => 'nullable|string|max:255|unique:vehicles,vehicle_number,' . $id,
            'vin' => 'nullable|string|max:17|unique:vehicles,vin,' . $id,
            'license_plate' => 'nullable|string|max:20',
            'registration_state' => 'nullable|string|max:2',
            'registration_expiration' => 'nullable|date',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'make' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'vehicle_type' => 'nullable|in:dump_truck,concrete_mixer,pickup,flatbed,lowboy,skid_steer_trailer,utility_van,service_truck',
            'fuel_type' => 'nullable|in:diesel,gasoline,electric,hybrid,propane',
            'tank_capacity_gallons' => 'nullable|numeric|min:0',
            'weight_class' => 'nullable|string|max:20',
            'gvwr_pounds' => 'nullable|integer|min:0',
            'towing_capacity_pounds' => 'nullable|integer|min:0',
            'requires_cdl' => 'nullable|boolean',
            'requires_dot_inspection' => 'nullable|boolean',
            'last_dot_inspection_date' => 'nullable|date',
            'next_dot_inspection_due' => 'nullable|date',
            'insurance_policy_number' => 'nullable|string|max:100',
            'insurance_provider' => 'nullable|string|max:255',
            'insurance_expiration' => 'nullable|date',
            'status' => 'nullable|in:active,inactive,maintenance,out_of_service,in_transit',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();

        $vehicle->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle updated successfully',
            'data' => $vehicle->load(['assignedTo']),
        ]);
    }

    /**
     * Remove the specified vehicle
     */
    public function destroy(int $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);

        $this->authorize('delete', $vehicle);

        $vehicle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vehicle deleted successfully',
        ]);
    }

    /**
     * Move vehicle to a new location
     */
    public function move(Request $request, int $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);

        $this->authorize('move', $vehicle);

        $validated = $request->validate([
            'location_type' => 'required|in:project,yard,shop,vendor,in_transit',
            'location_id' => 'nullable|integer',
            'movement_type' => 'required|in:pickup,dropoff,transfer,return_to_yard',
            'odometer_reading' => 'nullable|numeric|min:0',
            'gps_latitude' => 'nullable|numeric|between:-90,90',
            'gps_longitude' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string',
        ]);

        $movement = $this->vehicleService->moveVehicle(
            $id,
            $validated['location_type'],
            $validated['location_id'] ?? null,
            $validated['movement_type'],
            $validated['odometer_reading'] ?? null,
            $validated['gps_latitude'] ?? null,
            $validated['gps_longitude'] ?? null,
            false,
            null,
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Vehicle moved successfully',
            'data' => $movement,
        ]);
    }

    /**
     * Get movement history for a vehicle
     */
    public function history(int $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);

        $this->authorize('view', $vehicle);

        $history = $this->vehicleService->getVehicleHistory($id);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Update vehicle odometer reading
     */
    public function updateOdometer(Request $request, int $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);

        $this->authorize('update', $vehicle);

        $validated = $request->validate([
            'odometer_reading' => 'required|numeric|min:0',
        ]);

        $vehicle = $this->vehicleService->updateOdometer($id, $validated['odometer_reading']);

        return response()->json([
            'success' => true,
            'message' => 'Odometer updated successfully',
            'data' => $vehicle,
        ]);
    }

    /**
     * Get vehicles available for a specific date
     */
    public function availableForDate(string $date): JsonResponse
    {
        $this->authorize('viewAny', Vehicle::class);

        $availableVehicles = $this->vehicleService->getAvailableVehiclesForDate($date);

        return response()->json([
            'success' => true,
            'date' => $date,
            'data' => $availableVehicles,
        ]);
    }

    /**
     * Assign operator to vehicle
     */
    public function assignOperator(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $vehicle = Vehicle::findOrFail($id);

        $this->authorize('update', $vehicle);

        $vehicle->update([
            'assigned_to_user_id' => $validated['user_id'],
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Operator assigned successfully',
            'data' => $vehicle->load(['assignedTo']),
        ]);
    }

    /**
     * Generate QR code for vehicle
     */
    public function generateQrCode(int $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);

        $this->authorize('update', $vehicle);

        $qrCode = $this->vehicleService->generateQrCode($id);

        return response()->json([
            'success' => true,
            'message' => 'QR code generated successfully',
            'data' => [
                'qr_code' => $qrCode,
            ],
        ]);
    }
}
