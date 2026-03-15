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
        $admin = Role::where('slug', 'admin')->first();
        $admin->permissions()->attach(Permission::all());

        // Project Manager
        $pm = Role::where('slug', 'project_manager')->first();
        $pmPermissions = Permission::whereIn('module', ['projects', 'schedules', 'companies', 'contacts', 'equipment', 'reports'])
            ->get();
        $pm->permissions()->attach($pmPermissions);

        // Foreman
        $foreman = Role::where('slug', 'foreman')->first();
        $foremanPermissions = Permission::whereIn('module', ['schedules', 'equipment', 'inventory'])
            ->whereIn('name', [
                'schedules.view', 'schedules.update',
                'equipment.view', 'equipment.move',
                'inventory.view', 'inventory.move'
            ])
            ->get();
        $foreman->permissions()->attach($foremanPermissions);

        // Laborer - view only
        $laborer = Role::where('slug', 'laborer')->first();
        $laborerPermissions = Permission::where('name', 'like', '%.view')->get();
        $laborer->permissions()->attach($laborerPermissions);

        // Supervisor
        $supervisor = Role::where('slug', 'supervisor')->first();
        $supervisorPermissions = Permission::whereIn('module', ['schedules', 'equipment', 'inventory', 'reports'])
            ->get();
        $supervisor->permissions()->attach($supervisorPermissions);

        // Dispatch/Scheduling - transport permissions
        $dispatch = Role::where('slug', 'dispatch_scheduling')->first();
        if ($dispatch) {
            $dispatchTransport = Permission::where('module', 'transport')
                ->whereIn('name', ['transport.view', 'transport.create', 'transport.assign', 'transport.cancel'])
                ->get();
            $dispatch->permissions()->attach($dispatchTransport);
        }

        // Foreman - transport execute
        $foremanTransport = Permission::whereIn('name', ['transport.view', 'transport.execute'])->get();
        $foreman->permissions()->attach($foremanTransport);

        // Supervisor - transport view
        $supervisorTransport = Permission::where('name', 'transport.view')->get();
        $supervisor->permissions()->attach($supervisorTransport);

        // Laborer - transport view (already has all .view via wildcard above)
    }
}
