<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Vehicle;
use App\Models\MaintenanceRecord;
use App\Models\MaintenancePart;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class MaintenanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::where('email', 'admin@example.com')->first();

        // Get or create a vendor company for external maintenance
        $vendorCompany = Company::firstOrCreate(
            ['name' => 'Desert Equipment Services'],
            [
                'type' => 'vendor',
                'main_phone' => '602-555-9999',
                'main_email' => 'service@desertequip.com',
                'is_active' => true,
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ]
        );

        // Seed maintenance records for equipment
        $this->seedEquipmentMaintenance($adminUser, $vendorCompany);

        // Seed maintenance records for vehicles
        $this->seedVehicleMaintenance($adminUser, $vendorCompany);

        // Seed maintenance schedules
        $this->seedMaintenanceSchedules($adminUser, $vendorCompany);

        $this->command->info('Maintenance data seeded successfully!');
    }

    private function seedEquipmentMaintenance($adminUser, $vendorCompany)
    {
        $excavator = Equipment::where('equipment_number', 'EX-001')->first();
        $skidSteer = Equipment::where('equipment_number', 'SS-002')->first();
        $generator = Equipment::where('equipment_number', 'GN-001')->first();

        // CAT 320 Excavator - Recent oil change
        $oilChangeRecord = MaintenanceRecord::create([
            'maintainable_type' => 'App\\Models\\Equipment',
            'maintainable_id' => $excavator->id,
            'maintenance_type' => 'Oil & Filter Change',
            'category' => 'preventive',
            'performed_at' => Carbon::now()->subDays(15),
            'performed_by_type' => 'internal',
            'performed_by_user_id' => $adminUser->id,
            'hours_at_service' => 3400.0,
            'labor_hours' => 1.5,
            'labor_cost' => 75.00,
            'next_due_hours' => 3650.0,
            'description' => 'Regular scheduled oil and filter change',
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);

        MaintenancePart::create([
            'maintenance_record_id' => $oilChangeRecord->id,
            'part_name' => 'CAT Engine Oil 15W-40',
            'quantity' => 5,
            'unit_of_measure' => 'gal',
            'unit_price' => 24.99,
            'total_price' => 124.95,
            'part_type' => 'oem',
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);

        MaintenancePart::create([
            'maintenance_record_id' => $oilChangeRecord->id,
            'part_number' => '1R-0750',
            'part_name' => 'CAT Oil Filter',
            'quantity' => 1,
            'unit_of_measure' => 'ea',
            'unit_price' => 18.50,
            'total_price' => 18.50,
            'part_type' => 'oem',
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);

        $oilChangeRecord->update([
            'parts_cost' => 143.45,
            'total_cost' => 218.45,
        ]);

        // CAT 262D Skid Steer - Hydraulic pump replacement (currently in maintenance)
        $hydraulicRecord = MaintenanceRecord::create([
            'maintainable_type' => 'App\\Models\\Equipment',
            'maintainable_id' => $skidSteer->id,
            'maintenance_type' => 'Hydraulic Pump Replacement',
            'category' => 'corrective',
            'performed_at' => Carbon::now()->subDays(2),
            'performed_by_type' => 'vendor',
            'vendor_company_id' => $vendorCompany->id,
            'hours_at_service' => 2890.0,
            'labor_hours' => 8.0,
            'labor_cost' => 800.00,
            'work_order_number' => 'WO-2024-156',
            'description' => 'Main hydraulic pump failed, replaced with new OEM unit',
            'notes' => 'Also replaced hydraulic fluid and filters',
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);

        MaintenancePart::create([
            'maintenance_record_id' => $hydraulicRecord->id,
            'part_number' => '6685662',
            'part_name' => 'CAT Hydraulic Pump',
            'quantity' => 1,
            'unit_of_measure' => 'ea',
            'unit_price' => 2450.00,
            'total_price' => 2450.00,
            'vendor_company_id' => $vendorCompany->id,
            'part_type' => 'oem',
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);

        MaintenancePart::create([
            'maintenance_record_id' => $hydraulicRecord->id,
            'part_name' => 'CAT Hydraulic Fluid HYDO Advanced 10',
            'quantity' => 10,
            'unit_of_measure' => 'gal',
            'unit_price' => 32.99,
            'total_price' => 329.90,
            'part_type' => 'oem',
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);

        $hydraulicRecord->update([
            'parts_cost' => 2779.90,
            'total_cost' => 3579.90,
        ]);

        // CAT Generator - Warranty work
        $warrantyRecord = MaintenanceRecord::create([
            'maintainable_type' => 'App\\Models\\Equipment',
            'maintainable_id' => $generator->id,
            'maintenance_type' => 'Alternator Replacement',
            'category' => 'warranty',
            'performed_at' => Carbon::now()->subMonths(2),
            'performed_by_type' => 'vendor',
            'vendor_company_id' => $vendorCompany->id,
            'hours_at_service' => 4100.0,
            'labor_hours' => 3.0,
            'labor_cost' => 0.00,
            'is_warranty_work' => true,
            'warranty_claim_number' => 'CAT-WC-2024-7843',
            'warranty_provider' => 'Caterpillar Inc.',
            'work_order_number' => 'WO-2024-089',
            'description' => 'Alternator failure covered under manufacturer warranty',
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);

        MaintenancePart::create([
            'maintenance_record_id' => $warrantyRecord->id,
            'part_number' => '272-8115',
            'part_name' => 'CAT Alternator Assembly',
            'quantity' => 1,
            'unit_of_measure' => 'ea',
            'unit_price' => 0.00,
            'total_price' => 0.00,
            'is_warranty_part' => true,
            'part_type' => 'oem',
            'has_core_charge' => true,
            'core_charge_amount' => 150.00,
            'core_returned' => true,
            'core_returned_date' => Carbon::now()->subMonths(2)->addDays(7),
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);

        $warrantyRecord->update([
            'parts_cost' => 0.00,
            'total_cost' => 0.00,
        ]);
    }

    private function seedVehicleMaintenance($adminUser, $vendorCompany)
    {
        $dumpTruck1 = Vehicle::where('vehicle_number', 'DT-001')->first();
        $mixer = Vehicle::where('vehicle_number', 'MX-001')->first();
        $pickup = Vehicle::where('vehicle_number', 'PU-001')->first();

        // Mack Dump Truck - DOT Inspection
        MaintenanceRecord::create([
            'maintainable_type' => 'App\\Models\\Vehicle',
            'maintainable_id' => $dumpTruck1->id,
            'maintenance_type' => 'DOT Annual Inspection',
            'category' => 'inspection',
            'performed_at' => Carbon::now()->subMonths(10),
            'performed_by_type' => 'vendor',
            'vendor_company_id' => $vendorCompany->id,
            'odometer_at_service' => 78234.5,
            'labor_hours' => 2.0,
            'labor_cost' => 125.00,
            'next_due_date' => Carbon::now()->addMonths(2),
            'work_order_number' => 'WO-2024-012',
            'description' => 'Annual DOT safety inspection - passed',
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);

        // Peterbilt Mixer - Drum bearing replacement (in maintenance)
        $drumRecord = MaintenanceRecord::create([
            'maintainable_type' => 'App\\Models\\Vehicle',
            'maintainable_id' => $mixer->id,
            'maintenance_type' => 'Mixer Drum Bearing Replacement',
            'category' => 'corrective',
            'performed_at' => Carbon::now()->subDays(1),
            'performed_by_type' => 'internal',
            'performed_by_user_id' => $adminUser->id,
            'odometer_at_service' => 112345.3,
            'labor_hours' => 12.0,
            'labor_cost' => 600.00,
            'description' => 'Mixer drum bearing failure - replaced both bearings',
            'notes' => 'Work in progress - vehicle still in shop',
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);

        MaintenancePart::create([
            'maintenance_record_id' => $drumRecord->id,
            'part_number' => 'MB-4408',
            'part_name' => 'Mixer Drum Bearing Assembly',
            'quantity' => 2,
            'unit_of_measure' => 'ea',
            'unit_price' => 425.00,
            'total_price' => 850.00,
            'part_type' => 'aftermarket',
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);

        $drumRecord->update([
            'parts_cost' => 850.00,
            'total_cost' => 1450.00,
        ]);

        // Ford F-350 - Oil change
        $pickupOilChange = MaintenanceRecord::create([
            'maintainable_type' => 'App\\Models\\Vehicle',
            'maintainable_id' => $pickup->id,
            'maintenance_type' => 'Oil Change & Tire Rotation',
            'category' => 'preventive',
            'performed_at' => Carbon::now()->subDays(30),
            'performed_by_type' => 'internal',
            'performed_by_user_id' => $adminUser->id,
            'odometer_at_service' => 31234.5,
            'labor_hours' => 1.0,
            'labor_cost' => 50.00,
            'next_due_odometer' => 36234.5,
            'description' => 'Regular maintenance service',
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);

        MaintenancePart::create([
            'maintenance_record_id' => $pickupOilChange->id,
            'part_name' => 'Motorcraft Diesel Oil 5W-40',
            'quantity' => 3,
            'unit_of_measure' => 'gal',
            'unit_price' => 28.99,
            'total_price' => 86.97,
            'part_type' => 'oem',
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);

        MaintenancePart::create([
            'maintenance_record_id' => $pickupOilChange->id,
            'part_number' => 'FL-2051S',
            'part_name' => 'Motorcraft Oil Filter',
            'quantity' => 1,
            'unit_of_measure' => 'ea',
            'unit_price' => 12.99,
            'total_price' => 12.99,
            'part_type' => 'oem',
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);

        $pickupOilChange->update([
            'parts_cost' => 99.96,
            'total_cost' => 149.96,
        ]);
    }

    private function seedMaintenanceSchedules($adminUser, $vendorCompany)
    {
        $excavator = Equipment::where('equipment_number', 'EX-001')->first();
        $skidSteer = Equipment::where('equipment_number', 'SS-001')->first();
        $dumpTruck1 = Vehicle::where('vehicle_number', 'DT-001')->first();
        $dumpTruck2 = Vehicle::where('vehicle_number', 'DT-002')->first();
        $pickup = Vehicle::where('vehicle_number', 'PU-001')->first();

        // Equipment schedules
        MaintenanceSchedule::create([
            'maintainable_type' => 'App\\Models\\Equipment',
            'maintainable_id' => $excavator->id,
            'maintenance_type' => 'Oil & Filter Change',
            'description' => 'Regular engine oil and filter service',
            'category' => 'preventive',
            'frequency_type' => 'usage',
            'frequency_hours' => 250.0,
            'last_performed_hours' => 3400.0,
            'next_due_hours' => 3650.0,
            'notify_hours_before' => 25.0,
            'assigned_to_type' => 'internal',
            'assigned_user_id' => $adminUser->id,
            'estimated_cost' => 250.00,
            'estimated_labor_hours' => 1.5,
            'is_active' => true,
            'is_overdue' => false,
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);

        MaintenanceSchedule::create([
            'maintainable_type' => 'App\\Models\\Equipment',
            'maintainable_id' => $excavator->id,
            'maintenance_type' => 'Hydraulic Filter Change',
            'description' => 'Replace hydraulic system filters',
            'category' => 'preventive',
            'frequency_type' => 'usage',
            'frequency_hours' => 500.0,
            'last_performed_hours' => 3200.0,
            'next_due_hours' => 3700.0,
            'notify_hours_before' => 50.0,
            'assigned_to_type' => 'internal',
            'assigned_user_id' => $adminUser->id,
            'estimated_cost' => 180.00,
            'estimated_labor_hours' => 1.0,
            'is_active' => true,
            'is_overdue' => false,
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);

        MaintenanceSchedule::create([
            'maintainable_type' => 'App\\Models\\Equipment',
            'maintainable_id' => $skidSteer->id,
            'maintenance_type' => 'Oil & Filter Change',
            'description' => 'Regular engine oil and filter service',
            'category' => 'preventive',
            'frequency_type' => 'usage',
            'frequency_hours' => 200.0,
            'last_performed_hours' => 1200.0,
            'next_due_hours' => 1400.0,
            'notify_hours_before' => 20.0,
            'assigned_to_type' => 'internal',
            'assigned_user_id' => $adminUser->id,
            'estimated_cost' => 150.00,
            'estimated_labor_hours' => 1.0,
            'is_active' => true,
            'is_overdue' => false,
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);

        // Vehicle schedules
        MaintenanceSchedule::create([
            'maintainable_type' => 'App\\Models\\Vehicle',
            'maintainable_id' => $dumpTruck1->id,
            'maintenance_type' => 'DOT Annual Inspection',
            'description' => 'Required annual DOT safety inspection',
            'category' => 'inspection',
            'frequency_type' => 'calendar',
            'frequency_days' => 365,
            'last_performed_date' => Carbon::now()->subMonths(10),
            'next_due_date' => Carbon::now()->addMonths(2),
            'notify_days_before' => 30,
            'assigned_to_type' => 'vendor',
            'assigned_vendor_id' => $vendorCompany->id,
            'estimated_cost' => 150.00,
            'estimated_labor_hours' => 2.0,
            'is_active' => true,
            'is_overdue' => false,
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);

        MaintenanceSchedule::create([
            'maintainable_type' => 'App\\Models\\Vehicle',
            'maintainable_id' => $dumpTruck1->id,
            'maintenance_type' => 'Oil Change',
            'description' => 'Engine oil and filter service',
            'category' => 'preventive',
            'frequency_type' => 'hybrid',
            'frequency_days' => 180,
            'frequency_miles' => 15000.0,
            'last_performed_odometer' => 75000.0,
            'last_performed_date' => Carbon::now()->subMonths(5),
            'next_due_odometer' => 90000.0,
            'next_due_date' => Carbon::now()->addMonths(1),
            'notify_days_before' => 14,
            'notify_miles_before' => 500.0,
            'assigned_to_type' => 'internal',
            'assigned_user_id' => $adminUser->id,
            'estimated_cost' => 200.00,
            'estimated_labor_hours' => 1.5,
            'is_active' => true,
            'is_overdue' => false,
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);

        MaintenanceSchedule::create([
            'maintainable_type' => 'App\\Models\\Vehicle',
            'maintainable_id' => $dumpTruck2->id,
            'maintenance_type' => 'DOT Annual Inspection',
            'description' => 'Required annual DOT safety inspection',
            'category' => 'inspection',
            'frequency_type' => 'calendar',
            'frequency_days' => 365,
            'last_performed_date' => Carbon::now()->subMonths(8),
            'next_due_date' => Carbon::now()->addMonths(4),
            'notify_days_before' => 30,
            'assigned_to_type' => 'vendor',
            'assigned_vendor_id' => $vendorCompany->id,
            'estimated_cost' => 150.00,
            'estimated_labor_hours' => 2.0,
            'is_active' => true,
            'is_overdue' => false,
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);

        MaintenanceSchedule::create([
            'maintainable_type' => 'App\\Models\\Vehicle',
            'maintainable_id' => $pickup->id,
            'maintenance_type' => 'Oil Change & Tire Rotation',
            'description' => 'Regular maintenance service',
            'category' => 'preventive',
            'frequency_type' => 'usage',
            'frequency_miles' => 5000.0,
            'last_performed_odometer' => 31234.5,
            'next_due_odometer' => 36234.5,
            'notify_miles_before' => 500.0,
            'assigned_to_type' => 'internal',
            'assigned_user_id' => $adminUser->id,
            'estimated_cost' => 150.00,
            'estimated_labor_hours' => 1.0,
            'is_active' => true,
            'is_overdue' => false,
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);
    }
}
