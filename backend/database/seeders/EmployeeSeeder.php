<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('EmployeeSeeder is disabled in production.');
            return;
        }

        $adminId = 1;

        $employees = [
            // Office / Management
            [
                'first_name' => 'Maria',
                'last_name' => 'Gonzalez',
                'preferred_name' => 'Maria',
                'email' => 'maria.gonzalez@metconconcrete.com',
                'username' => 'mgonzalez',
                'phone' => '(555) 101-0001',
                'hourly_rate' => 45.00,
                'roles' => ['project_manager'],
            ],
            [
                'first_name' => 'James',
                'last_name' => 'Mitchell',
                'preferred_name' => 'Jim',
                'email' => 'james.mitchell@metconconcrete.com',
                'username' => 'jmitchell',
                'phone' => '(555) 101-0002',
                'hourly_rate' => 42.00,
                'roles' => ['estimator'],
            ],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Chen',
                'preferred_name' => 'Sarah',
                'email' => 'sarah.chen@metconconcrete.com',
                'username' => 'schen',
                'phone' => '(555) 101-0003',
                'hourly_rate' => 38.00,
                'roles' => ['dispatch_scheduling'],
            ],
            [
                'first_name' => 'Robert',
                'last_name' => 'Davis',
                'preferred_name' => 'Bob',
                'email' => 'robert.davis@metconconcrete.com',
                'username' => 'rdavis',
                'phone' => '(555) 101-0004',
                'hourly_rate' => 40.00,
                'roles' => ['safety_manager'],
            ],

            // Supervisors
            [
                'first_name' => 'Carlos',
                'last_name' => 'Ramirez',
                'preferred_name' => 'Carlos',
                'email' => 'carlos.ramirez@metconconcrete.com',
                'username' => 'cramirez',
                'phone' => '(555) 102-0001',
                'hourly_rate' => 38.00,
                'roles' => ['supervisor'],
            ],
            [
                'first_name' => 'David',
                'last_name' => 'Thompson',
                'preferred_name' => 'Dave',
                'email' => 'david.thompson@metconconcrete.com',
                'username' => 'dthompson',
                'phone' => '(555) 102-0002',
                'hourly_rate' => 36.00,
                'roles' => ['supervisor'],
            ],

            // Foremen
            [
                'first_name' => 'Miguel',
                'last_name' => 'Hernandez',
                'preferred_name' => 'Miguel',
                'email' => 'miguel.hernandez@metconconcrete.com',
                'username' => 'mhernandez',
                'phone' => '(555) 103-0001',
                'hourly_rate' => 32.00,
                'roles' => ['foreman'],
            ],
            [
                'first_name' => 'Tony',
                'last_name' => 'Nguyen',
                'preferred_name' => 'Tony',
                'email' => 'tony.nguyen@metconconcrete.com',
                'username' => 'tnguyen',
                'phone' => '(555) 103-0002',
                'hourly_rate' => 32.00,
                'roles' => ['foreman'],
            ],
            [
                'first_name' => 'Marcus',
                'last_name' => 'Johnson',
                'preferred_name' => 'Marcus',
                'email' => 'marcus.johnson@metconconcrete.com',
                'username' => 'mjohnson',
                'phone' => '(555) 103-0003',
                'hourly_rate' => 30.00,
                'roles' => ['foreman'],
            ],

            // Laborers
            [
                'first_name' => 'Jose',
                'last_name' => 'Martinez',
                'preferred_name' => 'Jose',
                'email' => 'jose.martinez@metconconcrete.com',
                'username' => 'jmartinez',
                'phone' => '(555) 104-0001',
                'hourly_rate' => 22.00,
                'roles' => ['laborer'],
            ],
            [
                'first_name' => 'Ryan',
                'last_name' => 'Williams',
                'preferred_name' => 'Ryan',
                'email' => 'ryan.williams@metconconcrete.com',
                'username' => 'rwilliams',
                'phone' => '(555) 104-0002',
                'hourly_rate' => 20.00,
                'roles' => ['laborer'],
            ],
            [
                'first_name' => 'Eduardo',
                'last_name' => 'Lopez',
                'preferred_name' => 'Eddie',
                'email' => 'eduardo.lopez@metconconcrete.com',
                'username' => 'elopez',
                'phone' => '(555) 104-0003',
                'hourly_rate' => 21.00,
                'roles' => ['laborer'],
            ],
            [
                'first_name' => 'Derek',
                'last_name' => 'Brown',
                'preferred_name' => 'Derek',
                'email' => 'derek.brown@metconconcrete.com',
                'username' => 'dbrown',
                'phone' => '(555) 104-0004',
                'hourly_rate' => 20.00,
                'roles' => ['laborer'],
            ],
            [
                'first_name' => 'Angel',
                'last_name' => 'Garcia',
                'preferred_name' => 'Angel',
                'email' => 'angel.garcia@metconconcrete.com',
                'username' => 'agarcia',
                'phone' => '(555) 104-0005',
                'hourly_rate' => 22.00,
                'roles' => ['laborer'],
            ],
            [
                'first_name' => 'Kevin',
                'last_name' => 'Patel',
                'preferred_name' => 'Kev',
                'email' => 'kevin.patel@metconconcrete.com',
                'username' => 'kpatel',
                'phone' => '(555) 104-0006',
                'hourly_rate' => 19.00,
                'roles' => ['laborer'],
            ],
            [
                'first_name' => 'Luis',
                'last_name' => 'Rivera',
                'preferred_name' => 'Luis',
                'email' => 'luis.rivera@metconconcrete.com',
                'username' => 'lrivera',
                'phone' => '(555) 104-0007',
                'hourly_rate' => 20.00,
                'roles' => ['laborer'],
            ],
            [
                'first_name' => 'Chris',
                'last_name' => 'Taylor',
                'preferred_name' => 'Chris',
                'email' => 'chris.taylor@metconconcrete.com',
                'username' => 'ctaylor',
                'phone' => '(555) 104-0008',
                'hourly_rate' => 21.00,
                'roles' => ['laborer'],
            ],
            [
                'first_name' => 'Manuel',
                'last_name' => 'Flores',
                'preferred_name' => 'Manny',
                'email' => 'manuel.flores@metconconcrete.com',
                'username' => 'mflores',
                'phone' => '(555) 104-0009',
                'hourly_rate' => 20.00,
                'roles' => ['laborer'],
            ],
        ];

        foreach ($employees as $empData) {
            $roleSlugs = $empData['roles'];
            unset($empData['roles']);

            $user = User::create(array_merge($empData, [
                'name' => $empData['first_name'] . ' ' . $empData['last_name'],
                'password' => env('SEED_DEFAULT_PASSWORD', 'password123'),
                'is_active' => true,
                'is_available' => true,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]));

            $roleIds = Role::whereIn('slug', $roleSlugs)->pluck('id');
            $user->roles()->attach($roleIds);
        }
    }
}
