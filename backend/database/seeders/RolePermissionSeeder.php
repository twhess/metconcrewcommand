<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Admin gets all permissions
        $admin = Role::where('name', 'admin')->first();
        $admin->permissions()->attach(Permission::all());

        // Project Manager
        $pm = Role::where('name', 'project_manager')->first();
        $pmPermissions = Permission::whereIn('module', ['projects', 'schedules', 'companies', 'contacts', 'equipment', 'reports'])
            ->get();
        $pm->permissions()->attach($pmPermissions);

        // Foreman
        $foreman = Role::where('name', 'foreman')->first();
        $foremanPermissions = Permission::whereIn('module', ['schedules', 'equipment', 'inventory'])
            ->whereIn('name', [
                'schedules.view', 'schedules.update',
                'equipment.view', 'equipment.move',
                'inventory.view', 'inventory.move'
            ])
            ->get();
        $foreman->permissions()->attach($foremanPermissions);

        // Laborer - view only
        $laborer = Role::where('name', 'laborer')->first();
        $laborerPermissions = Permission::where('name', 'like', '%.view')->get();
        $laborer->permissions()->attach($laborerPermissions);

        // Supervisor
        $supervisor = Role::where('name', 'supervisor')->first();
        $supervisorPermissions = Permission::whereIn('module', ['schedules', 'equipment', 'inventory', 'reports'])
            ->get();
        $supervisor->permissions()->attach($supervisorPermissions);
    }
}
