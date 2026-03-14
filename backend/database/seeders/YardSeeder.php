<?php

namespace Database\Seeders;

use App\Models\Yard;
use App\Models\User;
use Illuminate\Database\Seeder;

class YardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::where('email', 'admin@example.com')->first();

        $yards = [
            [
                'name' => 'Main Yard - Headquarters',
                'yard_type' => 'main_yard',
                'address_line1' => '1234 Industrial Parkway',
                'city' => 'Phoenix',
                'state' => 'AZ',
                'zip' => '85001',
                'gps_latitude' => 33.4484,
                'gps_longitude' => -112.0740,
                'gps_radius_feet' => 500,
                'contact_phone' => '602-555-0100',
                'contact_email' => 'mainyard@example.com',
                'is_active' => true,
                'notes' => 'Primary storage and dispatch location',
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ],
            [
                'name' => 'North Satellite Yard',
                'yard_type' => 'satellite_yard',
                'address_line1' => '5678 North Avenue',
                'city' => 'Scottsdale',
                'state' => 'AZ',
                'zip' => '85251',
                'gps_latitude' => 33.5092,
                'gps_longitude' => -111.8990,
                'gps_radius_feet' => 300,
                'contact_phone' => '480-555-0200',
                'is_active' => true,
                'notes' => 'Secondary storage for north projects',
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ],
            [
                'name' => 'Equipment Shop & Service Center',
                'yard_type' => 'shop',
                'address_line1' => '9101 Maintenance Drive',
                'city' => 'Mesa',
                'state' => 'AZ',
                'zip' => '85201',
                'gps_latitude' => 33.4152,
                'gps_longitude' => -111.8315,
                'gps_radius_feet' => 400,
                'contact_phone' => '480-555-0300',
                'contact_email' => 'shop@example.com',
                'is_active' => true,
                'notes' => 'Full service shop with certified mechanics',
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ],
            [
                'name' => 'West Storage Facility',
                'yard_type' => 'storage',
                'address_line1' => '2345 West Street',
                'city' => 'Glendale',
                'state' => 'AZ',
                'zip' => '85301',
                'gps_latitude' => 33.5387,
                'gps_longitude' => -112.1860,
                'gps_radius_feet' => 250,
                'is_active' => true,
                'notes' => 'Long-term storage for inactive equipment',
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ],
        ];

        foreach ($yards as $yardData) {
            Yard::create($yardData);
        }

        $this->command->info('Yards seeded successfully!');
    }
}
