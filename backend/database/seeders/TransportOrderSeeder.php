<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\TransportOrder;
use App\Services\EquipmentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class TransportOrderSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = 1;
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        // 1. Requested — excavator needs to move from yard to project, no driver yet
        TransportOrder::create([
            'order_number' => 'TO-' . now()->format('Ymd') . '-001',
            'status' => 'requested',
            'is_adhoc' => false,
            'priority' => 'normal',
            'equipment_id' => 1, // CAT 320 Excavator
            'pickup_location_type' => 'yard',
            'pickup_location_id' => 1, // Main Yard
            'dropoff_location_type' => 'project',
            'dropoff_location_id' => 2, // Easton Town Center
            'scheduled_date' => $today,
            'scheduled_time' => '08:00',
            'special_instructions' => 'Needs 2-axle trailer. Check hydraulic lines before loading.',
            'requested_by' => $adminId,
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);

        // 2. Assigned — generator going to project, driver + flatbed assigned, high priority
        TransportOrder::create([
            'order_number' => 'TO-' . now()->format('Ymd') . '-002',
            'status' => 'assigned',
            'is_adhoc' => false,
            'priority' => 'high',
            'equipment_id' => 6, // CAT C9 Generator
            'pickup_location_type' => 'yard',
            'pickup_location_id' => 1, // Main Yard
            'dropoff_location_type' => 'project',
            'dropoff_location_id' => 3, // OSU Medical Center
            'assigned_driver_id' => 8, // Miguel Hernandez (foreman)
            'assigned_vehicle_id' => 6, // International Flatbed
            'scheduled_date' => $today,
            'scheduled_time' => '06:30',
            'special_instructions' => 'Site requires hard hat and vest. Enter through gate B.',
            'requested_by' => $adminId,
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);

        // 3. Picked up — compactor in transit
        // First, simulate the equipment being picked up by creating a movement
        Auth::loginUsingId($adminId);
        $equipmentService = app(EquipmentService::class);

        $pickupMovement = $equipmentService->moveEquipment(
            equipmentId: 5, // CAT CB54 Plate Compactor
            locationType: 'in_transit',
            locationId: null,
            movementType: 'pickup',
        );

        $pickupMovement->update([
            'transported_by_user_id' => 9, // Tony Nguyen
            'transport_vehicle_id' => 7, // Trail King Lowboy
        ]);

        TransportOrder::create([
            'order_number' => 'TO-' . now()->format('Ymd') . '-003',
            'status' => 'picked_up',
            'is_adhoc' => false,
            'priority' => 'normal',
            'equipment_id' => 5, // CAT CB54 Plate Compactor
            'pickup_location_type' => 'yard',
            'pickup_location_id' => 3, // Equipment Shop
            'dropoff_location_type' => 'project',
            'dropoff_location_id' => 1, // Hilton Downtown
            'assigned_driver_id' => 9, // Tony Nguyen
            'assigned_vehicle_id' => 7, // Trail King Lowboy
            'scheduled_date' => $today,
            'scheduled_time' => '07:00',
            'requested_by' => $adminId,
            'picked_up_at' => now()->subHour(),
            'pickup_movement_id' => $pickupMovement->id,
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);

        // 4. Completed — skid steer delivered yesterday
        $completedTime = now()->subDay()->setHour(14);

        TransportOrder::create([
            'order_number' => 'TO-' . now()->subDay()->format('Ymd') . '-001',
            'status' => 'completed',
            'is_adhoc' => false,
            'priority' => 'normal',
            'equipment_id' => 3, // Bobcat S650
            'pickup_location_type' => 'yard',
            'pickup_location_id' => 1, // Main Yard
            'dropoff_location_type' => 'project',
            'dropoff_location_id' => 4, // Polaris Fashion Place
            'assigned_driver_id' => 10, // Marcus Johnson
            'assigned_vehicle_id' => 6, // International Flatbed
            'scheduled_date' => $yesterday,
            'scheduled_time' => '09:00',
            'requested_by' => $adminId,
            'picked_up_at' => $completedTime->copy()->subHours(2),
            'delivered_at' => $completedTime,
            'completed_at' => $completedTime,
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);

        // 5. Cancelled order
        TransportOrder::create([
            'order_number' => 'TO-' . now()->format('Ymd') . '-004',
            'status' => 'cancelled',
            'is_adhoc' => false,
            'priority' => 'low',
            'equipment_id' => 7, // Honda Portable Generator
            'pickup_location_type' => 'yard',
            'pickup_location_id' => 2, // North Satellite Yard
            'dropoff_location_type' => 'project',
            'dropoff_location_id' => 5, // Nationwide Arena
            'scheduled_date' => $today,
            'requested_by' => $adminId,
            'cancelled_at' => now()->subHours(3),
            'cancellation_reason' => 'Project postponed due to weather.',
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);
    }
}
