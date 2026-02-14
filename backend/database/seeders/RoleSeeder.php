<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            // System Roles
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'System administrator with full access'],

            // Contact Roles (for company contacts)
            ['name' => 'Project Manager', 'slug' => 'project_manager', 'description' => 'Primary contact for project coordination'],
            ['name' => 'Job Superintendent', 'slug' => 'job_superintendent', 'description' => 'On-site job superintendent'],
            ['name' => 'Job Supervisor', 'slug' => 'job_supervisor', 'description' => 'On-site job supervisor'],
            ['name' => 'Job Foreman', 'slug' => 'job_foreman', 'description' => 'On-site job foreman'],
            ['name' => 'Estimator', 'slug' => 'estimator', 'description' => 'Handles estimates and bidding'],
            ['name' => 'Purchasing', 'slug' => 'purchasing', 'description' => 'Handles purchasing and procurement'],
            ['name' => 'Accounts Payable', 'slug' => 'accounts_payable', 'description' => 'Handles accounts payable'],
            ['name' => 'Accounts Receivable', 'slug' => 'accounts_receivable', 'description' => 'Handles accounts receivable'],
            ['name' => 'Safety Manager', 'slug' => 'safety_manager', 'description' => 'Oversees safety compliance'],
            ['name' => 'Dispatch/Scheduling', 'slug' => 'dispatch_scheduling', 'description' => 'Handles dispatch and scheduling'],
            ['name' => 'Owner/Principal', 'slug' => 'owner_principal', 'description' => 'Company owner or principal'],

            // Crew Roles
            ['name' => 'Foreman', 'slug' => 'foreman', 'description' => 'Crew foreman'],
            ['name' => 'Supervisor', 'slug' => 'supervisor', 'description' => 'Crew supervisor'],
            ['name' => 'Laborer', 'slug' => 'laborer', 'description' => 'General laborer'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }
}
