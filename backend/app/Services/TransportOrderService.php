<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\TransportOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TransportOrderService
{
    protected EquipmentService $equipmentService;

    public function __construct(EquipmentService $equipmentService)
    {
        $this->equipmentService = $equipmentService;
    }

    /**
     * Generate a unique order number (TO-YYYYMMDD-NNN)
     */
    private function generateOrderNumber(): string
    {
        $dateStr = now()->format('Ymd');
        $prefix = "TO-{$dateStr}-";

        $lastOrder = TransportOrder::where('order_number', 'like', "{$prefix}%")
            ->orderBy('order_number', 'desc')
            ->first();

        if ($lastOrder) {
            $lastNum = (int) substr($lastOrder->order_number, -3);
            $nextNum = str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $nextNum = '001';
        }

        return $prefix . $nextNum;
    }

    /**
     * Create a new transport order
     */
    public function createOrder(array $data): TransportOrder
    {
        $equipment = Equipment::findOrFail($data['equipment_id']);

        // Check for conflicting active orders for this equipment
        $conflicting = TransportOrder::where('equipment_id', $data['equipment_id'])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->exists();

        if ($conflicting) {
            throw new \Exception('This equipment already has an active transport order.');
        }

        $userId = auth()->id();

        return TransportOrder::create([
            'order_number' => $this->generateOrderNumber(),
            'status' => 'requested',
            'is_adhoc' => false,
            'priority' => $data['priority'] ?? 'normal',
            'equipment_id' => $data['equipment_id'],
            'pickup_location_type' => $data['pickup_location_type'] ?? $equipment->current_location_type,
            'pickup_location_id' => $data['pickup_location_id'] ?? $equipment->current_location_id,
            'dropoff_location_type' => $data['dropoff_location_type'],
            'dropoff_location_id' => $data['dropoff_location_id'],
            'scheduled_date' => $data['scheduled_date'],
            'scheduled_time' => $data['scheduled_time'] ?? null,
            'special_instructions' => $data['special_instructions'] ?? null,
            'assigned_driver_id' => $data['assigned_driver_id'] ?? null,
            'assigned_vehicle_id' => $data['assigned_vehicle_id'] ?? null,
            'requested_by' => $userId,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    /**
     * Assign a driver and vehicle to an order
     */
    public function assignDriver(int $orderId, int $driverId, int $vehicleId): TransportOrder
    {
        $order = TransportOrder::findOrFail($orderId);

        if ($order->status !== 'requested') {
            throw new \Exception("Cannot assign driver to order in '{$order->status}' status.");
        }

        $order->update([
            'assigned_driver_id' => $driverId,
            'assigned_vehicle_id' => $vehicleId,
            'status' => 'assigned',
            'updated_by' => auth()->id(),
        ]);

        return $order->load(['assignedDriver', 'assignedVehicle', 'equipment']);
    }

    /**
     * Execute pickup — driver confirms equipment is on the truck
     */
    public function executePickup(int $orderId, array $pickupData): TransportOrder
    {
        $order = TransportOrder::findOrFail($orderId);

        if ($order->status !== 'assigned') {
            throw new \Exception("Cannot pickup order in '{$order->status}' status.");
        }

        DB::beginTransaction();
        try {
            // Create equipment movement via existing service
            $movement = $this->equipmentService->moveEquipment(
                equipmentId: $order->equipment_id,
                locationType: 'in_transit',
                locationId: null,
                movementType: 'pickup',
                hoursReading: $pickupData['hours_reading'] ?? null,
                gpsLat: $pickupData['gps_latitude'] ?? null,
                gpsLng: $pickupData['gps_longitude'] ?? null,
                scannedViaQr: $pickupData['scanned_via_qr'] ?? false,
                deviceInfo: $pickupData['device_info'] ?? null,
                notes: $pickupData['notes'] ?? null,
            );

            // Set transport fields on the movement
            $movement->update([
                'transported_by_user_id' => $order->assigned_driver_id,
                'transport_vehicle_id' => $order->assigned_vehicle_id,
            ]);

            // Update the transport order
            $order->update([
                'status' => 'picked_up',
                'picked_up_at' => now(),
                'pickup_movement_id' => $movement->id,
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            return $order->load(['equipment', 'assignedDriver', 'assignedVehicle', 'pickupMovement']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Execute dropoff — driver confirms equipment delivered to destination
     */
    public function executeDropoff(int $orderId, array $dropoffData): TransportOrder
    {
        $order = TransportOrder::findOrFail($orderId);

        if ($order->status !== 'picked_up') {
            throw new \Exception("Cannot dropoff order in '{$order->status}' status.");
        }

        $movementType = $order->dropoff_location_type === 'yard' ? 'return_to_yard' : 'dropoff';

        DB::beginTransaction();
        try {
            // Create equipment movement via existing service
            $movement = $this->equipmentService->moveEquipment(
                equipmentId: $order->equipment_id,
                locationType: $order->dropoff_location_type,
                locationId: $order->dropoff_location_id,
                movementType: $movementType,
                hoursReading: $dropoffData['hours_reading'] ?? null,
                gpsLat: $dropoffData['gps_latitude'] ?? null,
                gpsLng: $dropoffData['gps_longitude'] ?? null,
                scannedViaQr: $dropoffData['scanned_via_qr'] ?? false,
                deviceInfo: $dropoffData['device_info'] ?? null,
                notes: $dropoffData['notes'] ?? null,
            );

            // Set transport fields on the movement
            $movement->update([
                'transported_by_user_id' => $order->assigned_driver_id,
                'transport_vehicle_id' => $order->assigned_vehicle_id,
            ]);

            // Auto-complete on dropoff
            $now = now();
            $order->update([
                'status' => 'completed',
                'delivered_at' => $now,
                'completed_at' => $now,
                'dropoff_movement_id' => $movement->id,
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            return $order->load(['equipment', 'assignedDriver', 'assignedVehicle', 'dropoffMovement']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Ad-hoc pickup — driver initiates an unplanned move
     */
    public function createAdhocOrder(array $data): TransportOrder
    {
        $equipment = Equipment::findOrFail($data['equipment_id']);
        $userId = auth()->id();

        // Check for conflicting active orders
        $conflicting = TransportOrder::where('equipment_id', $data['equipment_id'])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->exists();

        if ($conflicting) {
            throw new \Exception('This equipment already has an active transport order.');
        }

        DB::beginTransaction();
        try {
            // Create the order in picked_up state
            $order = TransportOrder::create([
                'order_number' => $this->generateOrderNumber(),
                'status' => 'picked_up',
                'is_adhoc' => true,
                'priority' => $data['priority'] ?? 'normal',
                'equipment_id' => $data['equipment_id'],
                'pickup_location_type' => $equipment->current_location_type ?? 'yard',
                'pickup_location_id' => $equipment->current_location_id,
                'dropoff_location_type' => $data['dropoff_location_type'],
                'dropoff_location_id' => $data['dropoff_location_id'],
                'scheduled_date' => now()->toDateString(),
                'special_instructions' => $data['notes'] ?? null,
                'assigned_driver_id' => $userId,
                'assigned_vehicle_id' => $data['transport_vehicle_id'] ?? null,
                'requested_by' => $userId,
                'picked_up_at' => now(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // Create the pickup movement
            $movement = $this->equipmentService->moveEquipment(
                equipmentId: $data['equipment_id'],
                locationType: 'in_transit',
                locationId: null,
                movementType: 'pickup',
                hoursReading: $data['hours_reading'] ?? null,
                gpsLat: $data['gps_latitude'] ?? null,
                gpsLng: $data['gps_longitude'] ?? null,
                scannedViaQr: $data['scanned_via_qr'] ?? false,
                deviceInfo: $data['device_info'] ?? null,
                notes: $data['notes'] ?? null,
            );

            $movement->update([
                'transported_by_user_id' => $userId,
                'transport_vehicle_id' => $data['transport_vehicle_id'] ?? null,
            ]);

            $order->update(['pickup_movement_id' => $movement->id]);

            DB::commit();

            return $order->load(['equipment', 'assignedDriver', 'assignedVehicle']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(int $orderId, string $reason): TransportOrder
    {
        $order = TransportOrder::findOrFail($orderId);

        if ($order->status === 'picked_up') {
            throw new \Exception('Cannot cancel an order with equipment in transit. Drop off first.');
        }

        if (in_array($order->status, ['completed', 'cancelled'])) {
            throw new \Exception("Order is already {$order->status}.");
        }

        $order->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'updated_by' => auth()->id(),
        ]);

        return $order;
    }

    /**
     * Get orders for dispatch dashboard with filters
     */
    public function getOrdersForDispatch(array $filters = []): Collection
    {
        $query = TransportOrder::with([
            'equipment',
            'assignedDriver',
            'assignedVehicle',
            'requestedByUser',
        ]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date'])) {
            $query->where('scheduled_date', $filters['date']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['driver_id'])) {
            $query->where('assigned_driver_id', $filters['driver_id']);
        }

        if (isset($filters['is_adhoc'])) {
            $query->where('is_adhoc', $filters['is_adhoc']);
        }

        return $query->orderByRaw("FIELD(priority, ?, ?, ?, ?)", ['urgent', 'high', 'normal', 'low'])
            ->orderBy('scheduled_time')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get orders assigned to a specific driver
     */
    public function getOrdersForDriver(int $userId, ?string $date = null): Collection
    {
        $query = TransportOrder::with(['equipment', 'assignedVehicle'])
            ->where('assigned_driver_id', $userId)
            ->whereIn('status', ['assigned', 'picked_up']);

        // Also include completed today
        $today = $date ?? now()->toDateString();
        $query->orWhere(function ($q) use ($userId, $today) {
            $q->where('assigned_driver_id', $userId)
                ->where('status', 'completed')
                ->whereDate('completed_at', $today);
        });

        return $query->orderByRaw("FIELD(status, ?, ?, ?)", ['picked_up', 'assigned', 'completed'])
            ->orderByRaw("FIELD(priority, ?, ?, ?, ?)", ['urgent', 'high', 'normal', 'low'])
            ->get();
    }

    /**
     * Get summary counts for dispatch dashboard
     */
    public function getDispatchSummary(?string $date = null): array
    {
        $date = $date ?? now()->toDateString();

        $counts = TransportOrder::where('scheduled_date', $date)
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Also count in-transit regardless of scheduled date
        $inTransit = TransportOrder::where('status', 'picked_up')->count();

        return [
            'date' => $date,
            'counts' => [
                'requested' => $counts['requested'] ?? 0,
                'assigned' => $counts['assigned'] ?? 0,
                'picked_up' => $inTransit,
                'completed' => $counts['completed'] ?? 0,
                'cancelled' => $counts['cancelled'] ?? 0,
            ],
        ];
    }
}
