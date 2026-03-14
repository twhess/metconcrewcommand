<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Vehicle;
use App\Services\EquipmentService;
use App\Services\VehicleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TransportController extends Controller
{
    protected EquipmentService $equipmentService;
    protected VehicleService $vehicleService;

    public function __construct(EquipmentService $equipmentService, VehicleService $vehicleService)
    {
        $this->equipmentService = $equipmentService;
        $this->vehicleService = $vehicleService;
    }

    /**
     * Scan QR code to get item information
     * Public endpoint - returns item info for scan page
     */
    public function scanQrCode(string $qrCode): JsonResponse
    {
        // Check if it's equipment
        $equipment = Equipment::where('qr_code', $qrCode)->first();
        if ($equipment) {
            $equipment->load(['latestMovement.movedByUser', 'currentProject', 'currentYard']);

            return response()->json([
                'success' => true,
                'item_type' => 'equipment',
                'item' => $equipment,
                'current_location' => $this->formatLocation($equipment->current_location_type, $equipment->current_location_id, $equipment),
                'suggested_actions' => $this->getSuggestedActions($equipment->status, $equipment->current_location_type),
            ]);
        }

        // Check if it's a vehicle
        $vehicle = Vehicle::where('qr_code', $qrCode)->first();
        if ($vehicle) {
            $vehicle->load(['latestMovement.movedByUser', 'currentProject', 'currentYard', 'assignedTo']);

            return response()->json([
                'success' => true,
                'item_type' => 'vehicle',
                'item' => $vehicle,
                'current_location' => $this->formatLocation($vehicle->current_location_type, $vehicle->current_location_id, $vehicle),
                'suggested_actions' => $this->getSuggestedActions($vehicle->status, $vehicle->current_location_type),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'QR code not found in system',
        ], 404);
    }

    /**
     * Pickup equipment/vehicle (manual or QR-based)
     */
    public function pickup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_type' => 'required|in:equipment,vehicle',
            'item_id' => 'required_without:qr_code|integer',
            'qr_code' => 'required_without:item_id|string',

            // Transport details
            'transported_by_user_id' => 'nullable|exists:users,id', // Defaults to auth user if not provided
            'transport_vehicle_id' => 'nullable|exists:vehicles,id',
            'temp_transport_vehicle' => 'nullable|string|max:255', // e.g., "Rental Truck - ABC123"

            // Rental tracking (for equipment only)
            'is_rental' => 'nullable|boolean',
            'rental_company' => 'nullable|string|max:255',
            'rental_agreement_number' => 'nullable|string|max:255',

            // Readings
            'odometer_reading' => 'nullable|numeric|min:0', // For vehicles
            'hours_reading' => 'nullable|numeric|min:0', // For equipment with hour meters

            // GPS
            'gps_latitude' => 'nullable|numeric|between:-90,90',
            'gps_longitude' => 'nullable|numeric|between:-180,180',

            // Metadata
            'scanned_via_qr' => 'nullable|boolean',
            'device_info' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Resolve item
        $item = $this->resolveItem($validated);

        // Default transporter to authenticated user
        $transportedBy = $validated['transported_by_user_id'] ?? auth()->id();

        if ($validated['item_type'] === 'equipment') {
            $movement = $this->equipmentService->moveEquipment(
                equipmentId: $item->id,
                locationType: 'in_transit',
                locationId: null,
                movementType: 'pickup',
                hoursReading: $validated['hours_reading'] ?? null,
                gpsLat: $validated['gps_latitude'] ?? null,
                gpsLng: $validated['gps_longitude'] ?? null,
                scannedViaQr: $validated['scanned_via_qr'] ?? false,
                deviceInfo: $validated['device_info'] ?? null,
                notes: $validated['notes'] ?? null
            );

            // Update with transport-specific fields
            $movement->update([
                'transported_by_user_id' => $transportedBy,
                'transport_vehicle_id' => $validated['transport_vehicle_id'] ?? null,
                'temp_transport_vehicle' => $validated['temp_transport_vehicle'] ?? null,
                'is_rental' => $validated['is_rental'] ?? false,
                'rental_company' => $validated['rental_company'] ?? null,
                'rental_agreement_number' => $validated['rental_agreement_number'] ?? null,
            ]);
        } else {
            // Vehicle
            $movement = $this->vehicleService->moveVehicle(
                vehicleId: $item->id,
                locationType: 'in_transit',
                locationId: null,
                movementType: 'pickup',
                odometerReading: $validated['odometer_reading'] ?? null,
                gpsLat: $validated['gps_latitude'] ?? null,
                gpsLng: $validated['gps_longitude'] ?? null,
                scannedViaQr: $validated['scanned_via_qr'] ?? false,
                deviceInfo: $validated['device_info'] ?? null,
                notes: $validated['notes'] ?? null
            );

            // Update with transport-specific fields
            $movement->update([
                'transported_by_user_id' => $transportedBy,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => ucfirst($validated['item_type']) . ' picked up successfully',
            'data' => $movement->load(['movedByUser', $validated['item_type']]),
        ]);
    }

    /**
     * Dropoff equipment/vehicle (manual or QR-based)
     */
    public function dropoff(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_type' => 'required|in:equipment,vehicle',
            'item_id' => 'required_without:qr_code|integer',
            'qr_code' => 'required_without:item_id|string',

            // Destination
            'location_type' => 'required|in:project,yard,shop',
            'location_id' => 'required|integer',

            // Readings
            'odometer_reading' => 'nullable|numeric|min:0',
            'hours_reading' => 'nullable|numeric|min:0',

            // GPS
            'gps_latitude' => 'nullable|numeric|between:-90,90',
            'gps_longitude' => 'nullable|numeric|between:-180,180',

            // Metadata
            'scanned_via_qr' => 'nullable|boolean',
            'device_info' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Resolve item
        $item = $this->resolveItem($validated);

        // Verify item is in transit
        if (!$item->isInTransit()) {
            return response()->json([
                'success' => false,
                'message' => 'Item is not currently in transit',
            ], 422);
        }

        $movementType = $validated['location_type'] === 'yard' ? 'return_to_yard' : 'dropoff';

        if ($validated['item_type'] === 'equipment') {
            $movement = $this->equipmentService->moveEquipment(
                equipmentId: $item->id,
                locationType: $validated['location_type'],
                locationId: $validated['location_id'],
                movementType: $movementType,
                hoursReading: $validated['hours_reading'] ?? null,
                gpsLat: $validated['gps_latitude'] ?? null,
                gpsLng: $validated['gps_longitude'] ?? null,
                scannedViaQr: $validated['scanned_via_qr'] ?? false,
                deviceInfo: $validated['device_info'] ?? null,
                notes: $validated['notes'] ?? null
            );
        } else {
            $movement = $this->vehicleService->moveVehicle(
                vehicleId: $item->id,
                locationType: $validated['location_type'],
                locationId: $validated['location_id'],
                movementType: $movementType,
                odometerReading: $validated['odometer_reading'] ?? null,
                gpsLat: $validated['gps_latitude'] ?? null,
                gpsLng: $validated['gps_longitude'] ?? null,
                scannedViaQr: $validated['scanned_via_qr'] ?? false,
                deviceInfo: $validated['device_info'] ?? null,
                notes: $validated['notes'] ?? null
            );
        }

        return response()->json([
            'success' => true,
            'message' => ucfirst($validated['item_type']) . ' dropped off successfully',
            'data' => $movement->load(['movedByUser', $validated['item_type']]),
        ]);
    }

    /**
     * Get all items currently in transit
     */
    public function itemsInTransit(): JsonResponse
    {
        $equipment = Equipment::where('status', 'in_transit')
            ->orWhere('current_location_type', 'in_transit')
            ->with(['latestMovement.movedByUser', 'latestMovement.transportedByUser', 'latestMovement.transportVehicle'])
            ->get();

        $vehicles = Vehicle::where('status', 'in_transit')
            ->orWhere('current_location_type', 'in_transit')
            ->with(['latestMovement.movedByUser', 'latestMovement.transportedByUser'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'equipment' => $equipment,
                'vehicles' => $vehicles,
                'total_count' => $equipment->count() + $vehicles->count(),
            ],
        ]);
    }

    /**
     * Get active transports for the authenticated user
     */
    public function myActiveTransports(): JsonResponse
    {
        $userId = auth()->id();

        $equipmentMovements = \App\Models\EquipmentMovement::where('transported_by_user_id', $userId)
            ->whereHas('equipment', function ($query) {
                $query->where('status', 'in_transit')
                    ->orWhere('current_location_type', 'in_transit');
            })
            ->with(['equipment', 'movedByUser'])
            ->orderBy('moved_at', 'desc')
            ->get();

        $vehicleMovements = \App\Models\VehicleMovement::where('transported_by_user_id', $userId)
            ->whereHas('vehicle', function ($query) {
                $query->where('status', 'in_transit')
                    ->orWhere('current_location_type', 'in_transit');
            })
            ->with(['vehicle', 'movedByUser'])
            ->orderBy('moved_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'equipment_transports' => $equipmentMovements,
                'vehicle_transports' => $vehicleMovements,
                'total_active' => $equipmentMovements->count() + $vehicleMovements->count(),
            ],
        ]);
    }

    /**
     * Helper: Resolve item from request data
     */
    private function resolveItem(array $validated)
    {
        if (isset($validated['qr_code'])) {
            if ($validated['item_type'] === 'equipment') {
                return Equipment::where('qr_code', $validated['qr_code'])->firstOrFail();
            } else {
                return Vehicle::where('qr_code', $validated['qr_code'])->firstOrFail();
            }
        } else {
            if ($validated['item_type'] === 'equipment') {
                return Equipment::findOrFail($validated['item_id']);
            } else {
                return Vehicle::findOrFail($validated['item_id']);
            }
        }
    }

    /**
     * Helper: Format location information
     */
    private function formatLocation(?string $locationType, ?int $locationId, $item): array
    {
        if (!$locationType) {
            return [
                'type' => null,
                'id' => null,
                'name' => 'Not Set',
                'gps_lat' => null,
                'gps_lng' => null,
            ];
        }

        $location = [
            'type' => $locationType,
            'id' => $locationId,
            'gps_lat' => $item->current_location_gps_lat,
            'gps_lng' => $item->current_location_gps_lng,
        ];

        if ($locationType === 'in_transit') {
            $location['name'] = 'In Transit';
        } elseif ($locationType === 'project' && $locationId) {
            $project = \App\Models\Project::find($locationId);
            $location['name'] = $project ? $project->name : 'Unknown Project';
        } elseif (in_array($locationType, ['yard', 'shop']) && $locationId) {
            $yard = \App\Models\Yard::find($locationId);
            $location['name'] = $yard ? $yard->name : 'Unknown Yard';
        } else {
            $location['name'] = ucfirst($locationType);
        }

        return $location;
    }

    /**
     * Helper: Get suggested actions based on current state
     */
    private function getSuggestedActions(string $status, ?string $locationType): array
    {
        $actions = [];

        if ($status === 'in_transit' || $locationType === 'in_transit') {
            $actions[] = [
                'action' => 'dropoff',
                'label' => 'Drop Off',
                'enabled' => true,
            ];
        } else {
            $actions[] = [
                'action' => 'pickup',
                'label' => 'Pick Up',
                'enabled' => true,
            ];
        }

        return $actions;
    }
}
