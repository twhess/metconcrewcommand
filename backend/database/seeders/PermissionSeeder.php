<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            'users' => ['view', 'create', 'update', 'delete'],
            'roles' => ['view', 'create', 'update', 'delete'],
            'companies' => ['view', 'create', 'update', 'delete'],
            'contacts' => ['view', 'create', 'update', 'delete'],
            'projects' => ['view', 'create', 'update', 'delete'],
            'schedules' => ['view', 'create', 'update', 'delete'],
            'equipment' => ['view', 'create', 'update', 'delete', 'move'],
            'inventory' => ['view', 'create', 'update', 'delete', 'move', 'adjust'],
            'reports' => ['view', 'export'],
            'vacations' => ['view', 'create', 'update', 'delete', 'approve'],
        ];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::create([
                    'name' => "{$module}.{$action}",
                    'display_name' => ucfirst($action) . ' ' . ucfirst($module),
                    'module' => $module,
                ]);
            }
        }
    }
}
