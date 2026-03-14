<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\EquipmentMovement;
use App\Models\Project;
use App\Models\Yard;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EquipmentService
{
    /**
     * Move equipment to a new location
     *
     * @param int $equipmentId
     * @param string $locationType 'project', 'yard', 'shop', 'location', 'in_transit'
     * @param int|null $locationId
     * @param string $movementType 'pickup', 'dropoff', 'transfer', 'return_to_yard'
     * @param float|null $hoursReading
     * @param float|null $gpsLat
     * @param float|null $gpsLng
     * @param bool $scannedViaQr
     * @param string|null $deviceInfo
     * @param string|null $notes
     * @return EquipmentMovement
     */
    public function moveEquipment(
        int $equipmentId,
        string $locationType,
        ?int $locationId,
        string $movementType = 'transfer',
        ?float $hoursReading = null,
        ?float $gpsLat = null,
        ?float $gpsLng = null,
        bool $scannedViaQr = false,
        ?string $deviceInfo = null,
        ?string $notes = null
    ): EquipmentMovement {
        $equipment = Equipment::findOrFail($equipmentId);

        DB::beginTransaction();
        try {
            // Log the movement
            $movement = EquipmentMovement::create([
                'equipment_id' => $equipmentId,
                'from_location_type' => $equipment->current_location_type,
                'from_location_id' => $equipment->current_location_id,
                'from_location_gps_lat' => $equipment->current_location_gps_lat,
                'from_location_gps_lng' => $equipment->current_location_gps_lng,
                'to_location_type' => $locationType,
                'to_location_id' => $locationId,
                'to_location_gps_lat' => $gpsLat,
                'to_location_gps_lng' => $gpsLng,
                'movement_type' => $movementType,
                'hours_reading' => $hoursReading,
                'moved_at' => now(),
                'moved_by' => auth()->id(),
                'scanned_via_qr' => $scannedViaQr,
                'device_info' => $deviceInfo,
                'notes' => $notes,
            ]);

            // Update equipment's current location
            $updateData = [
                'current_location_type' => $locationType,
                'current_location_id' => $locationId,
                'current_location_gps_lat' => $gpsLat,
                'current_location_gps_lng' => $gpsLng,
                'updated_by' => auth()->id(),
            ];

            // Update hour meter if provided
            if ($hoursReading !== null && $equipment->has_hour_meter) {
                $updateData['current_hours'] = $hoursReading;
                $updateData['last_hours_reading_at'] = now();
            }

            // Set status to in_transit if movement type is pickup
            if ($movementType === 'pickup') {
                $updateData['status'] = 'in_transit';
            }

            // Set status to active if movement type is dropoff or return_to_yard
            if (in_array($movementType, ['dropoff', 'return_to_yard'])) {
                $updateData['status'] = 'active';
            }

            $equipment->update($updateData);

            DB::commit();

            return $movement->load(['movedByUser', 'equipment']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get equipment location history
     */
    public function getEquipmentHistory(int $equipmentId): array
    {
        return EquipmentMovement::where('equipment_id', $equipmentId)
            ->with(['movedByUser'])
            ->orderBy('moved_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get all equipment at a specific location
     */
    public function getEquipmentAtLocation(string $locationType, ?int $locationId = null): array
    {
        $query = Equipment::where('current_location_type', $locationType);

        if ($locationId !== null) {
            $query->where('current_location_id', $locationId);
        } else {
            $query->whereNull('current_location_id');
        }

        return $query->get()->toArray();
    }

    /**
     * Get equipment location report
     */
    public function getLocationReport(): array
    {
        $equipment = Equipment::with(['currentProject', 'latestMovement'])
            ->where('status', 'active')
            ->get();

        return $equipment->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'equipment_number' => $item->equipment_number,
                'type' => $item->type,
                'category' => $item->category,
                'current_location_type' => $item->current_location_type,
                'current_location_id' => $item->current_location_id,
                'current_location_name' => $this->getLocationName($item),
                'last_moved_at' => optional($item->latestMovement)->moved_at,
                'last_moved_by' => optional(optional($item->latestMovement)->movedByUser)->name,
            ];
        })->toArray();
    }

    /**
     * Get human-readable location name
     */
    private function getLocationName(Equipment $equipment): ?string
    {
        if (!$equipment->current_location_type) {
            return 'Not Set';
        }

        if ($equipment->current_location_type === 'in_transit') {
            return 'In Transit';
        }

        if ($equipment->current_location_type === 'available') {
            return 'Available Pool';
        }

        if ($equipment->current_location_type === 'project' && $equipment->current_location_id) {
            $project = Project::find($equipment->current_location_id);
            return $project ? $project->name : 'Unknown Project';
        }

        if ($equipment->current_location_type === 'yard' && $equipment->current_location_id) {
            $yard = Yard::find($equipment->current_location_id);
            return $yard ? $yard->name : 'Unknown Yard';
        }

        if ($equipment->current_location_type === 'shop' && $equipment->current_location_id) {
            $yard = Yard::find($equipment->current_location_id);
            return $yard ? $yard->name . ' (Shop)' : 'Unknown Shop';
        }

        if ($equipment->current_location_type === 'location' && $equipment->current_location_id) {
            $location = \App\Models\InventoryLocation::find($equipment->current_location_id);
            return $location ? $location->name : 'Unknown Location';
        }

        return ucfirst($equipment->current_location_type);
    }

    /**
     * Move multiple equipment items to the same location
     */
    public function bulkMoveEquipment(
        array $equipmentIds,
        string $locationType,
        ?int $locationId,
        string $movementType = 'transfer',
        ?string $notes = null
    ): array {
        $moved = [];
        $errors = [];

        foreach ($equipmentIds as $equipmentId) {
            try {
                $movement = $this->moveEquipment(
                    $equipmentId,
                    $locationType,
                    $locationId,
                    $movementType,
                    null,
                    null,
                    null,
                    false,
                    null,
                    $notes
                );
                $moved[] = $equipmentId;
            } catch (\Exception $e) {
                $errors[] = [
                    'equipment_id' => $equipmentId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'moved' => $moved,
            'errors' => $errors,
        ];
    }

    /**
     * Update equipment hour meter reading
     */
    public function updateHours(int $equipmentId, float $hoursReading): Equipment
    {
        $equipment = Equipment::findOrFail($equipmentId);

        if (!$equipment->has_hour_meter) {
            throw new \Exception('This equipment does not have an hour meter.');
        }

        $equipment->update([
            'current_hours' => $hoursReading,
            'last_hours_reading_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        return $equipment;
    }

    /**
     * Generate unique QR code for equipment
     */
    public function generateQrCode(int $equipmentId): string
    {
        $equipment = Equipment::findOrFail($equipmentId);

        // Generate unique QR code if not already exists
        if (!$equipment->qr_code) {
            $qrCode = 'EQP-' . strtoupper(uniqid());

            $equipment->update([
                'qr_code' => $qrCode,
                'updated_by' => auth()->id(),
            ]);

            return $qrCode;
        }

        return $equipment->qr_code;
    }

    /**
     * Get equipment currently in transit
     */
    public function getEquipmentInTransit(): array
    {
        return Equipment::where('status', 'in_transit')
            ->orWhere('current_location_type', 'in_transit')
            ->with(['latestMovement.movedByUser'])
            ->get()
            ->toArray();
    }
}
