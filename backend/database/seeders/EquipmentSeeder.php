<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Yard;
use App\Models\User;
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::where('email', 'admin@example.com')->first();
        $mainYard = Yard::where('yard_type', 'main_yard')->first();
        $shop = Yard::where('yard_type', 'shop')->first();

        $equipment = [
            // Excavators
            [
                'name' => 'CAT 320 Excavator',
                'equipment_number' => 'EX-001',
                'qr_code' => 'EQP-EX001',
                'type' => 'trackable',
                'category' => 'excavator',
                'status' => 'active',
                'current_location_type' => 'yard',
                'current_location_id' => $mainYard->id,
                'current_location_gps_lat' => $mainYard->gps_latitude,
                'current_location_gps_lng' => $mainYard->gps_longitude,
                'has_hour_meter' => true,
                'current_hours' => 3450.5,
                'description' => '2018 CAT 320 Hydraulic Excavator, 36" bucket',
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ],
            [
                'name' => 'Komatsu PC210 Excavator',
                'equipment_number' => 'EX-002',
                'qr_code' => 'EQP-EX002',
                'type' => 'trackable',
                'category' => 'excavator',
                'status' => 'active',
                'current_location_type' => 'yard',
                'current_location_id' => $mainYard->id,
                'current_location_gps_lat' => $mainYard->gps_latitude,
                'current_location_gps_lng' => $mainYard->gps_longitude,
                'has_hour_meter' => true,
                'current_hours' => 2180.0,
                'description' => '2020 Komatsu PC210 Excavator',
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ],

            // Skid Steers
            [
                'name' => 'Bobcat S650 Skid Steer',
                'equipment_number' => 'SS-001',
                'qr_code' => 'EQP-SS001',
                'type' => 'trackable',
                'category' => 'skid_steer',
                'status' => 'active',
                'current_location_type' => 'yard',
                'current_location_id' => $mainYard->id,
                'current_location_gps_lat' => $mainYard->gps_latitude,
                'current_location_gps_lng' => $mainYard->gps_longitude,
                'has_hour_meter' => true,
                'current_hours' => 1245.5,
                'description' => '2019 Bobcat S650 with bucket attachment',
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ],
            [
                'name' => 'CAT 262D Skid Steer',
                'equipment_number' => 'SS-002',
                'qr_code' => 'EQP-SS002',
                'type' => 'trackable',
                'category' => 'skid_steer',
                'status' => 'maintenance',
                'current_location_type' => 'yard',
                'current_location_id' => $shop->id,
                'current_location_gps_lat' => $shop->gps_latitude,
                'current_location_gps_lng' => $shop->gps_longitude,
                'has_hour_meter' => true,
                'current_hours' => 2890.0,
                'description' => '2017 CAT 262D - Currently in for hydraulic service',
                'notes' => 'Scheduled for hydraulic pump replacement',
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ],

            // Compactors
            [
                'name' => 'CAT CB54 Plate Compactor',
                'equipment_number' => 'CP-001',
                'qr_code' => 'EQP-CP001',
                'type' => 'trackable',
                'category' => 'compactor',
                'status' => 'active',
                'current_location_type' => 'yard',
                'current_location_id' => $mainYard->id,
                'current_location_gps_lat' => $mainYard->gps_latitude,
                'current_location_gps_lng' => $mainYard->gps_longitude,
                'has_hour_meter' => true,
                'current_hours' => 567.5,
                'description' => 'Walk-behind plate compactor',
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ],

            // Generators
            [
                'name' => 'CAT C9 Generator 275kW',
                'equipment_number' => 'GN-001',
                'qr_code' => 'EQP-GN001',
                'type' => 'trackable',
                'category' => 'generator',
                'status' => 'active',
                'current_location_type' => 'yard',
                'current_location_id' => $mainYard->id,
                'current_location_gps_lat' => $mainYard->gps_latitude,
                'current_location_gps_lng' => $mainYard->gps_longitude,
                'has_hour_meter' => true,
                'current_hours' => 4321.0,
                'description' => '275kW diesel generator on trailer',
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ],
            [
                'name' => 'Honda EU2200i Portable Generator',
                'equipment_number' => 'GN-002',
                'qr_code' => 'EQP-GN002',
                'type' => 'trackable',
                'category' => 'generator',
                'status' => 'active',
                'current_location_type' => 'yard',
                'current_location_id' => $mainYard->id,
                'current_location_gps_lat' => $mainYard->gps_latitude,
                'current_location_gps_lng' => $mainYard->gps_longitude,
                'has_hour_meter' => true,
                'current_hours' => 123.5,
                'description' => '2200W portable inverter generator',
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ],

            // Concrete Equipment
            [
                'name' => 'Multiquip Concrete Vibrator',
                'equipment_number' => 'CV-001',
                'qr_code' => 'EQP-CV001',
                'type' => 'trackable',
                'category' => 'concrete_tools',
                'status' => 'active',
                'current_location_type' => 'yard',
                'current_location_id' => $mainYard->id,
                'current_location_gps_lat' => $mainYard->gps_latitude,
                'current_location_gps_lng' => $mainYard->gps_longitude,
                'has_hour_meter' => false,
                'current_hours' => 0,
                'description' => 'Gas-powered concrete vibrator with 20ft shaft',
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ],
            [
                'name' => 'Allen Engineering Ride-On Trowel',
                'equipment_number' => 'TR-001',
                'qr_code' => 'EQP-TR001',
                'type' => 'trackable',
                'category' => 'concrete_tools',
                'status' => 'active',
                'current_location_type' => 'yard',
                'current_location_id' => $mainYard->id,
                'current_location_gps_lat' => $mainYard->gps_latitude,
                'current_location_gps_lng' => $mainYard->gps_longitude,
                'has_hour_meter' => true,
                'current_hours' => 890.0,
                'description' => '46" ride-on power trowel for finishing',
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ],

            // Air Compressors
            [
                'name' => 'Atlas Copco XAS185 Air Compressor',
                'equipment_number' => 'AC-001',
                'qr_code' => 'EQP-AC001',
                'type' => 'trackable',
                'category' => 'air_compressor',
                'status' => 'active',
                'current_location_type' => 'yard',
                'current_location_id' => $mainYard->id,
                'current_location_gps_lat' => $mainYard->gps_latitude,
                'current_location_gps_lng' => $mainYard->gps_longitude,
                'has_hour_meter' => true,
                'current_hours' => 2456.0,
                'description' => '185 CFM portable air compressor',
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ],

            // Water Pumps
            [
                'name' => 'Gorman-Rupp 4" Trash Pump',
                'equipment_number' => 'WP-001',
                'qr_code' => 'EQP-WP001',
                'type' => 'trackable',
                'category' => 'pump',
                'status' => 'active',
                'current_location_type' => 'yard',
                'current_location_id' => $mainYard->id,
                'current_location_gps_lat' => $mainYard->gps_latitude,
                'current_location_gps_lng' => $mainYard->gps_longitude,
                'has_hour_meter' => true,
                'current_hours' => 678.5,
                'description' => '4" diesel trash pump with hoses',
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ],

            // Small Tools & Equipment
            [
                'name' => 'Hilti TE 3000-AVR Breaker',
                'equipment_number' => 'BR-001',
                'qr_code' => 'EQP-BR001',
                'type' => 'trackable',
                'category' => 'small_tools',
                'status' => 'active',
                'current_location_type' => 'yard',
                'current_location_id' => $mainYard->id,
                'current_location_gps_lat' => $mainYard->gps_latitude,
                'current_location_gps_lng' => $mainYard->gps_longitude,
                'has_hour_meter' => false,
                'current_hours' => 0,
                'description' => 'Electric jackhammer for demo work',
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ],
        ];

        foreach ($equipment as $equipmentData) {
            Equipment::create($equipmentData);
        }

        $this->command->info('Equipment seeded successfully!');
    }
}
