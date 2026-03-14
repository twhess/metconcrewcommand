<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            [
                'name' => 'Turner Construction',
                'type' => 'general_contractor',
                'main_phone' => '(614) 555-1000',
                'main_email' => 'info@turnerconstruction.example.com',
                'website' => 'https://turnerconstruction.example.com',
                'notes' => 'Large GC, frequent commercial projects',
                'locations' => [
                    [
                        'location_name' => 'Main Office',
                        'location_type' => 'office',
                        'is_primary' => true,
                        'address1' => '500 S Front St',
                        'city' => 'Columbus',
                        'state' => 'OH',
                        'zip' => '43215',
                        'phone' => '(614) 555-1001',
                        'hours' => 'Mon-Fri 7am-5pm',
                    ],
                    [
                        'location_name' => 'Equipment Yard',
                        'location_type' => 'yard',
                        'is_primary' => false,
                        'address1' => '2100 Westbelt Dr',
                        'city' => 'Columbus',
                        'state' => 'OH',
                        'zip' => '43228',
                        'phone' => '(614) 555-1002',
                        'hours' => 'Mon-Fri 6am-4pm',
                    ],
                ],
                'contacts' => [
                    [
                        'first_name' => 'Mike',
                        'last_name' => 'Reynolds',
                        'title' => 'Senior Project Manager',
                        'phone_work' => '(614) 555-1010',
                        'email' => 'mreynolds@turnerconstruction.example.com',
                        'role_slug' => 'project_manager',
                    ],
                    [
                        'first_name' => 'Sarah',
                        'last_name' => 'Chen',
                        'title' => 'Estimator',
                        'phone_work' => '(614) 555-1011',
                        'email' => 'schen@turnerconstruction.example.com',
                        'role_slug' => 'estimator',
                    ],
                    [
                        'first_name' => 'Dave',
                        'last_name' => 'Kowalski',
                        'title' => 'Job Superintendent',
                        'phone_work' => '(614) 555-1012',
                        'email' => 'dkowalski@turnerconstruction.example.com',
                        'role_slug' => 'job_superintendent',
                    ],
                ],
            ],
            [
                'name' => 'Messer Construction',
                'type' => 'general_contractor',
                'main_phone' => '(513) 555-2000',
                'main_email' => 'info@messer.example.com',
                'website' => 'https://messer.example.com',
                'notes' => 'Regional GC, healthcare and education focus',
                'locations' => [
                    [
                        'location_name' => 'Cincinnati HQ',
                        'location_type' => 'office',
                        'is_primary' => true,
                        'address1' => '4245 Hunt Rd',
                        'city' => 'Cincinnati',
                        'state' => 'OH',
                        'zip' => '45242',
                        'phone' => '(513) 555-2001',
                        'hours' => 'Mon-Fri 7am-5pm',
                    ],
                    [
                        'location_name' => 'Columbus Office',
                        'location_type' => 'office',
                        'is_primary' => false,
                        'address1' => '1250 Kinnear Rd',
                        'city' => 'Columbus',
                        'state' => 'OH',
                        'zip' => '43212',
                        'phone' => '(614) 555-2002',
                        'hours' => 'Mon-Fri 7:30am-4:30pm',
                    ],
                ],
                'contacts' => [
                    [
                        'first_name' => 'Lisa',
                        'last_name' => 'Martinez',
                        'title' => 'Project Manager',
                        'phone_work' => '(513) 555-2010',
                        'email' => 'lmartinez@messer.example.com',
                        'role_slug' => 'project_manager',
                    ],
                    [
                        'first_name' => 'Tom',
                        'last_name' => 'Bradley',
                        'title' => 'Purchasing Agent',
                        'phone_work' => '(513) 555-2011',
                        'email' => 'tbradley@messer.example.com',
                        'role_slug' => 'purchasing',
                    ],
                ],
            ],
            [
                'name' => 'Central Ohio Concrete Supply',
                'type' => 'supplier',
                'main_phone' => '(614) 555-3000',
                'main_email' => 'orders@coconcrete.example.com',
                'website' => 'https://coconcrete.example.com',
                'notes' => 'Primary concrete supplier, same-day delivery available',
                'locations' => [
                    [
                        'location_name' => 'Batch Plant - West',
                        'location_type' => 'yard',
                        'is_primary' => true,
                        'address1' => '3800 W Broad St',
                        'city' => 'Columbus',
                        'state' => 'OH',
                        'zip' => '43228',
                        'phone' => '(614) 555-3001',
                        'hours' => 'Mon-Sat 5am-5pm',
                    ],
                    [
                        'location_name' => 'Batch Plant - East',
                        'location_type' => 'yard',
                        'is_primary' => false,
                        'address1' => '5600 E Main St',
                        'city' => 'Columbus',
                        'state' => 'OH',
                        'zip' => '43213',
                        'phone' => '(614) 555-3002',
                        'hours' => 'Mon-Sat 5am-5pm',
                    ],
                    [
                        'location_name' => 'Billing Office',
                        'location_type' => 'billing',
                        'is_primary' => false,
                        'address1' => '100 E Broad St, Suite 400',
                        'city' => 'Columbus',
                        'state' => 'OH',
                        'zip' => '43215',
                        'phone' => '(614) 555-3003',
                        'hours' => 'Mon-Fri 8am-5pm',
                    ],
                ],
                'contacts' => [
                    [
                        'first_name' => 'Jim',
                        'last_name' => 'Patterson',
                        'title' => 'Dispatch Manager',
                        'phone_work' => '(614) 555-3010',
                        'email' => 'jpatterson@coconcrete.example.com',
                        'role_slug' => 'dispatch_scheduling',
                    ],
                    [
                        'first_name' => 'Karen',
                        'last_name' => 'Nguyen',
                        'title' => 'Accounts Receivable',
                        'phone_work' => '(614) 555-3011',
                        'email' => 'knguyen@coconcrete.example.com',
                        'role_slug' => 'accounts_receivable',
                    ],
                ],
            ],
            [
                'name' => 'Buckeye Rebar & Steel',
                'type' => 'supplier',
                'main_phone' => '(614) 555-4000',
                'main_email' => 'sales@buckeyerebar.example.com',
                'website' => 'https://buckeyerebar.example.com',
                'notes' => 'Rebar fabrication and delivery, 2-week lead time typical',
                'locations' => [
                    [
                        'location_name' => 'Fabrication Shop',
                        'location_type' => 'yard',
                        'is_primary' => true,
                        'address1' => '1500 Marion Rd',
                        'city' => 'Columbus',
                        'state' => 'OH',
                        'zip' => '43207',
                        'phone' => '(614) 555-4001',
                        'hours' => 'Mon-Fri 6am-3:30pm',
                    ],
                ],
                'contacts' => [
                    [
                        'first_name' => 'Rick',
                        'last_name' => 'Hoffman',
                        'title' => 'Sales Manager',
                        'phone_work' => '(614) 555-4010',
                        'email' => 'rhoffman@buckeyerebar.example.com',
                        'role_slug' => 'owner_principal',
                    ],
                    [
                        'first_name' => 'Angela',
                        'last_name' => 'Torres',
                        'title' => 'Accounts Payable',
                        'phone_work' => '(614) 555-4011',
                        'email' => 'atorres@buckeyerebar.example.com',
                        'role_slug' => 'accounts_payable',
                    ],
                ],
            ],
            [
                'name' => 'SafeFirst Environmental',
                'type' => 'subcontractor',
                'main_phone' => '(614) 555-5000',
                'main_email' => 'info@safefirst.example.com',
                'website' => 'https://safefirst.example.com',
                'notes' => 'Environmental testing and compliance services',
                'locations' => [
                    [
                        'location_name' => 'Main Office & Lab',
                        'location_type' => 'office',
                        'is_primary' => true,
                        'address1' => '880 W Henderson Rd',
                        'city' => 'Columbus',
                        'state' => 'OH',
                        'zip' => '43214',
                        'phone' => '(614) 555-5001',
                        'hours' => 'Mon-Fri 8am-5pm',
                    ],
                ],
                'contacts' => [
                    [
                        'first_name' => 'Rachel',
                        'last_name' => 'Kim',
                        'title' => 'Safety Director',
                        'phone_work' => '(614) 555-5010',
                        'email' => 'rkim@safefirst.example.com',
                        'role_slug' => 'safety_manager',
                    ],
                ],
            ],
        ];

        foreach ($companies as $companyData) {
            $locations = $companyData['locations'];
            $contacts = $companyData['contacts'];
            unset($companyData['locations'], $companyData['contacts']);

            $company = Company::create($companyData);

            // Create locations
            $locationModels = [];
            foreach ($locations as $locationData) {
                $location = $company->locations()->create($locationData);
                if ($location->is_primary) {
                    $locationModels['primary'] = $location;
                }
            }

            // Create contacts and assign roles at primary location
            foreach ($contacts as $contactData) {
                $roleSlug = $contactData['role_slug'];
                unset($contactData['role_slug']);

                $contact = $company->contacts()->create($contactData);

                // Assign role with primary location
                $role = Role::where('slug', $roleSlug)->first();
                if ($role) {
                    $pivotData = ['is_primary_for_role' => true];
                    if (isset($locationModels['primary'])) {
                        $pivotData['location_id'] = $locationModels['primary']->id;
                    }
                    $contact->roles()->attach($role->id, $pivotData);
                }
            }
        }
    }
}
