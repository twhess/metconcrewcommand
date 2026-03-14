<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleMovement;
use App\Models\Project;
use App\Models\Yard;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VehicleService
{
    /**
     * Move vehicle to a new location
     *
     * @param int $vehicleId
     * @param string $locationType 'project', 'yard', 'shop', 'vendor', 'in_transit'
     * @param int|null $locationId
     * @param string $movementType 'pickup', 'dropoff', 'transfer', 'return_to_yard'
     * @param float|null $odometerReading
     * @param float|null $gpsLat
     * @param float|null $gpsLng
     * @param bool $scannedViaQr
     * @param string|null $deviceInfo
     * @param string|null $notes
     * @return VehicleMovement
     */
    public function moveVehicle(
        int $vehicleId,
        string $locationType,
        ?int $locationId,
        string $movementType = 'transfer',
        ?float $odometerReading = null,
        ?float $gpsLat = null,
        ?float $gpsLng = null,
        bool $scannedViaQr = false,
        ?string $deviceInfo = null,
        ?string $notes = null
    ): VehicleMovement {
        $vehicle = Vehicle::findOrFail($vehicleId);

        DB::beginTransaction();
        try {
            // Log the movement
            $movement = VehicleMovement::create([
                'vehicle_id' => $vehicleId,
                'from_location_type' => $vehicle->current_location_type,
                'from_location_id' => $vehicle->current_location_id,
                'from_location_gps_lat' => $vehicle->current_location_gps_lat,
                'from_location_gps_lng' => $vehicle->current_location_gps_lng,
                'to_location_type' => $locationType,
                'to_location_id' => $locationId,
                'to_location_gps_lat' => $gpsLat,
                'to_location_gps_lng' => $gpsLng,
                'movement_type' => $movementType,
                'odometer_reading' => $odometerReading,
                'moved_at' => now(),
                'moved_by' => auth()->id(),
                'scanned_via_qr' => $scannedViaQr,
                'device_info' => $deviceInfo,
                'notes' => $notes,
            ]);

            // Update vehicle's current location
            $updateData = [
                'current_location_type' => $locationType,
                'current_location_id' => $locationId,
                'current_location_gps_lat' => $gpsLat,
                'current_location_gps_lng' => $gpsLng,
                'updated_by' => auth()->id(),
            ];

            // Update odometer if provided
            if ($odometerReading !== null) {
                $updateData['current_odometer_miles'] = $odometerReading;
                $updateData['last_odometer_reading_at'] = now();
            }

            // Set status to in_transit if movement type is pickup
            if ($movementType === 'pickup') {
                $updateData['status'] = 'in_transit';
            }

            // Set status to active if movement type is dropoff or return_to_yard
            if (in_array($movementType, ['dropoff', 'return_to_yard'])) {
                $updateData['status'] = 'active';
            }

            $vehicle->update($updateData);

            DB::commit();

            return $movement->load(['movedByUser', 'vehicle']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update vehicle odometer reading
     */
    public function updateOdometer(int $vehicleId, float $odometerReading): Vehicle
    {
        $vehicle = Vehicle::findOrFail($vehicleId);

        $vehicle->update([
            'current_odometer_miles' => $odometerReading,
            'last_odometer_reading_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        return $vehicle;
    }

    /**
     * Get vehicle movement history
     */
    public function getVehicleHistory(int $vehicleId): array
    {
        return VehicleMovement::where('vehicle_id', $vehicleId)
            ->with(['movedByUser'])
            ->orderBy('moved_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get all vehicles at a specific location
     */
    public function getVehiclesAtLocation(string $locationType, ?int $locationId = null): array
    {
        $query = Vehicle::where('current_location_type', $locationType);

        if ($locationId !== null) {
            $query->where('current_location_id', $locationId);
        } else {
            $query->whereNull('current_location_id');
        }

        return $query->with(['assignedTo'])->get()->toArray();
    }

    /**
     * Get vehicle location report
     */
    public function getLocationReport(): array
    {
        $vehicles = Vehicle::with(['currentProject', 'currentYard', 'latestMovement', 'assignedTo'])
            ->where('status', '!=', 'inactive')
            ->get();

        return $vehicles->map(function ($vehicle) {
            return [
                'id' => $vehicle->id,
                'name' => $vehicle->name,
                'vehicle_number' => $vehicle->vehicle_number,
                'vehicle_type' => $vehicle->vehicle_type,
                'status' => $vehicle->status,
                'current_location_type' => $vehicle->current_location_type,
                'current_location_id' => $vehicle->current_location_id,
                'current_location_name' => $this->getLocationName($vehicle),
                'assigned_to' => optional($vehicle->assignedTo)->name,
                'last_moved_at' => optional($vehicle->latestMovement)->moved_at,
                'last_moved_by' => optional(optional($vehicle->latestMovement)->movedByUser)->name,
            ];
        })->toArray();
    }

    /**
     * Get human-readable location name
     */
    private function getLocationName(Vehicle $vehicle): ?string
    {
        if (!$vehicle->current_location_type) {
            return 'Not Set';
        }

        if ($vehicle->current_location_type === 'in_transit') {
            return 'In Transit';
        }

        if ($vehicle->current_location_type === 'project' && $vehicle->current_location_id) {
            $project = Project::find($vehicle->current_location_id);
            return $project ? $project->name : 'Unknown Project';
        }

        if ($vehicle->current_location_type === 'yard' && $vehicle->current_location_id) {
            $yard = Yard::find($vehicle->current_location_id);
            return $yard ? $yard->name : 'Unknown Yard';
        }

        if ($vehicle->current_location_type === 'shop' && $vehicle->current_location_id) {
            $yard = Yard::find($vehicle->current_location_id);
            return $yard ? $yard->name . ' (Shop)' : 'Unknown Shop';
        }

        return ucfirst($vehicle->current_location_type);
    }

    /**
     * Move multiple vehicles to the same location
     */
    public function bulkMoveVehicles(
        array $vehicleIds,
        string $locationType,
        ?int $locationId,
        string $movementType = 'transfer',
        ?string $notes = null
    ): array {
        $moved = [];
        $errors = [];

        foreach ($vehicleIds as $vehicleId) {
            try {
                $movement = $this->moveVehicle(
                    $vehicleId,
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
                $moved[] = $vehicleId;
            } catch (\Exception $e) {
                $errors[] = [
                    'vehicle_id' => $vehicleId,
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
     * Get vehicles available for a specific date
     */
    public function getAvailableVehiclesForDate(string $date): array
    {
        // For now, return all active vehicles not in maintenance or out of service
        // TODO: In future, check against schedule assignments
        return Vehicle::where('status', 'active')
            ->with(['assignedTo'])
            ->get()
            ->toArray();
    }

    /**
     * Generate unique QR code for vehicle
     */
    public function generateQrCode(int $vehicleId): string
    {
        $vehicle = Vehicle::findOrFail($vehicleId);

        // Generate unique QR code if not already exists
        if (!$vehicle->qr_code) {
            $qrCode = 'VEH-' . strtoupper(uniqid());

            $vehicle->update([
                'qr_code' => $qrCode,
                'updated_by' => auth()->id(),
            ]);

            return $qrCode;
        }

        return $vehicle->qr_code;
    }

    /**
     * Get vehicles due for DOT inspection
     */
    public function getVehiclesDueForDotInspection(int $daysAhead = 30): array
    {
        $futureDate = Carbon::now()->addDays($daysAhead);

        return Vehicle::where('requires_dot_inspection', true)
            ->where('status', '!=', 'inactive')
            ->where(function ($query) use ($futureDate) {
                $query->whereNull('next_dot_inspection_due')
                    ->orWhere('next_dot_inspection_due', '<=', $futureDate);
            })
            ->with(['assignedTo'])
            ->get()
            ->toArray();
    }

    /**
     * Get vehicles with expiring insurance
     */
    public function getVehiclesWithExpiringInsurance(int $daysAhead = 30): array
    {
        $futureDate = Carbon::now()->addDays($daysAhead);

        return Vehicle::where('status', '!=', 'inactive')
            ->where('insurance_expiration', '<=', $futureDate)
            ->whereNotNull('insurance_expiration')
            ->with(['assignedTo'])
            ->get()
            ->toArray();
    }

    /**
     * Get vehicles with expiring registration
     */
    public function getVehiclesWithExpiringRegistration(int $daysAhead = 30): array
    {
        $futureDate = Carbon::now()->addDays($daysAhead);

        return Vehicle::where('status', '!=', 'inactive')
            ->where('registration_expiration', '<=', $futureDate)
            ->whereNotNull('registration_expiration')
            ->with(['assignedTo'])
            ->get()
            ->toArray();
    }
}
