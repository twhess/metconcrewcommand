<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles first
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
        ]);

        // Create admin user
        $admin = User::factory()->create([
            'username' => 'admin',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'is_active' => true,
            'is_available' => true,
        ]);

        // Assign admin role to the user
        $adminRole = \App\Models\Role::where('slug', 'admin')->first();
        $admin->roles()->attach($adminRole);

        // Seed companies with locations and contacts
        $this->call(CompanySeeder::class);

        // Seed yards, vehicles, equipment, and maintenance
        $this->call([
            YardSeeder::class,
            EquipmentSeeder::class,
            VehicleSeeder::class,
            MaintenanceSeeder::class,
        ]);
    }
}
