<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ProjectContact;
use App\Models\ProjectVendor;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        // Project 1: Large commercial foundation - Turner Construction
        $project1 = Project::create([
            'company_id' => 1, // Turner Construction
            'name' => 'Hilton Downtown Columbus Foundation',
            'project_number' => 'PRJ-2026-001',
            'status' => 'active',
            'project_type' => 'foundation',
            'specification_template_id' => 1,
            'address_line1' => '250 N High Street',
            'city' => 'Columbus',
            'state' => 'OH',
            'zip' => '43215',
            'start_date' => '2026-03-01',
            'end_date' => '2026-08-15',
            'description' => '12-story hotel foundation with deep footings, grade beams, and elevator pits. Approximately 4,200 CY of concrete.',
            'notes' => 'Night pours required for large mat foundations. City permits in hand.',
            'created_by' => 1,
        ]);

        // Phases for Project 1
        ProjectPhase::create([
            'project_id' => $project1->id,
            'name' => 'Excavation & Shoring',
            'phase_number' => 1,
            'status' => 'completed',
            'estimated_start_date' => '2026-03-01',
            'estimated_end_date' => '2026-03-21',
            'start_date' => '2026-03-03',
            'end_date' => '2026-03-20',
            'estimated_hours' => 320,
            'actual_hours' => 296,
            'completion_percentage' => 100,
            'crew_size_estimate' => 8,
            'equipment_needs' => json_encode(['excavator', 'backhoe', 'compactor']),
            'notes' => 'Completed ahead of schedule.',
            'completed_at' => '2026-03-20',
            'created_by' => 1,
        ]);

        ProjectPhase::create([
            'project_id' => $project1->id,
            'name' => 'Footings & Grade Beams',
            'phase_number' => 2,
            'status' => 'in_progress',
            'estimated_start_date' => '2026-03-22',
            'estimated_end_date' => '2026-04-30',
            'start_date' => '2026-03-22',
            'estimated_hours' => 640,
            'actual_hours' => 280,
            'completion_percentage' => 45,
            'crew_size_estimate' => 12,
            'equipment_needs' => json_encode(['concrete_pump', 'vibrator', 'laser_level']),
            'notes' => 'Currently forming section B footings.',
            'created_by' => 1,
        ]);

        ProjectPhase::create([
            'project_id' => $project1->id,
            'name' => 'Foundation Walls',
            'phase_number' => 3,
            'status' => 'pending',
            'estimated_start_date' => '2026-05-01',
            'estimated_end_date' => '2026-06-15',
            'estimated_hours' => 800,
            'completion_percentage' => 0,
            'crew_size_estimate' => 14,
            'equipment_needs' => json_encode(['crane', 'concrete_pump', 'form_system']),
            'created_by' => 1,
        ]);

        ProjectPhase::create([
            'project_id' => $project1->id,
            'name' => 'Elevator Pits & Slab on Grade',
            'phase_number' => 4,
            'status' => 'pending',
            'estimated_start_date' => '2026-06-16',
            'estimated_end_date' => '2026-08-15',
            'estimated_hours' => 560,
            'completion_percentage' => 0,
            'crew_size_estimate' => 10,
            'equipment_needs' => json_encode(['power_screed', 'finishing_machine', 'saw']),
            'created_by' => 1,
        ]);

        // Contacts for Project 1
        ProjectContact::create([
            'project_id' => $project1->id,
            'contact_id' => 1, // Mike Reynolds - Turner
            'role' => 'superintendent',
            'is_primary' => true,
            'notes' => 'On-site daily. Direct contact for scheduling.',
            'created_by' => 1,
        ]);

        ProjectContact::create([
            'project_id' => $project1->id,
            'contact_id' => 2, // Sarah Chen - Turner
            'role' => 'project_manager',
            'is_primary' => true,
            'created_by' => 1,
        ]);

        ProjectContact::create([
            'project_id' => $project1->id,
            'contact_id' => 3, // Dave Kowalski - Turner
            'role' => 'estimator',
            'is_primary' => false,
            'created_by' => 1,
        ]);

        // Vendors for Project 1
        ProjectVendor::create([
            'project_id' => $project1->id,
            'company_id' => 3, // Central Ohio Concrete Supply
            'vendor_type' => 'concrete_supplier',
            'is_primary' => true,
            'contract_number' => 'CO-2026-0445',
            'notes' => '5000 PSI mix design approved. Delivery schedule confirmed.',
            'created_by' => 1,
        ]);

        ProjectVendor::create([
            'project_id' => $project1->id,
            'company_id' => 4, // Buckeye Rebar & Steel
            'vendor_type' => 'rebar_supplier',
            'is_primary' => true,
            'contract_number' => 'BR-2026-112',
            'notes' => '#5 and #8 rebar. Shop drawings approved.',
            'created_by' => 1,
        ]);

        // Project 2: Flatwork - Messer Construction
        $project2 = Project::create([
            'company_id' => 2, // Messer Construction
            'name' => 'Easton Town Center Parking Expansion',
            'project_number' => 'PRJ-2026-002',
            'status' => 'active',
            'project_type' => 'flatwork',
            'specification_template_id' => 2,
            'address_line1' => '4100 Easton Gateway Drive',
            'city' => 'Columbus',
            'state' => 'OH',
            'zip' => '43219',
            'start_date' => '2026-02-15',
            'end_date' => '2026-05-30',
            'description' => 'New 400-space parking structure ground level flatwork. 18,000 SF of 6" reinforced concrete with integral color.',
            'notes' => 'Work restricted to overnight hours due to retail traffic.',
            'created_by' => 1,
        ]);

        ProjectPhase::create([
            'project_id' => $project2->id,
            'name' => 'Subgrade Preparation',
            'phase_number' => 1,
            'status' => 'completed',
            'estimated_start_date' => '2026-02-15',
            'estimated_end_date' => '2026-03-01',
            'start_date' => '2026-02-17',
            'end_date' => '2026-03-02',
            'estimated_hours' => 200,
            'actual_hours' => 216,
            'completion_percentage' => 100,
            'crew_size_estimate' => 6,
            'completed_at' => '2026-03-02',
            'created_by' => 1,
        ]);

        ProjectPhase::create([
            'project_id' => $project2->id,
            'name' => 'Section A Paving',
            'phase_number' => 2,
            'status' => 'in_progress',
            'estimated_start_date' => '2026-03-03',
            'estimated_end_date' => '2026-04-01',
            'start_date' => '2026-03-05',
            'estimated_hours' => 400,
            'actual_hours' => 160,
            'completion_percentage' => 60,
            'crew_size_estimate' => 8,
            'equipment_needs' => json_encode(['laser_screed', 'power_trowel', 'saw']),
            'notes' => 'Night pours Mon-Thu only.',
            'created_by' => 1,
        ]);

        ProjectPhase::create([
            'project_id' => $project2->id,
            'name' => 'Section B Paving',
            'phase_number' => 3,
            'status' => 'pending',
            'estimated_start_date' => '2026-04-02',
            'estimated_end_date' => '2026-05-01',
            'estimated_hours' => 400,
            'completion_percentage' => 0,
            'crew_size_estimate' => 8,
            'created_by' => 1,
        ]);

        ProjectPhase::create([
            'project_id' => $project2->id,
            'name' => 'Curbs & Striping Prep',
            'phase_number' => 4,
            'status' => 'pending',
            'estimated_start_date' => '2026-05-02',
            'estimated_end_date' => '2026-05-30',
            'estimated_hours' => 240,
            'completion_percentage' => 0,
            'crew_size_estimate' => 6,
            'created_by' => 1,
        ]);

        ProjectContact::create([
            'project_id' => $project2->id,
            'contact_id' => 4, // Lisa Martinez - Messer
            'role' => 'project_manager',
            'is_primary' => true,
            'created_by' => 1,
        ]);

        ProjectContact::create([
            'project_id' => $project2->id,
            'contact_id' => 5, // Tom Bradley - Messer
            'role' => 'superintendent',
            'is_primary' => true,
            'notes' => 'Night shift superintendent.',
            'created_by' => 1,
        ]);

        ProjectVendor::create([
            'project_id' => $project2->id,
            'company_id' => 3, // Central Ohio Concrete Supply
            'vendor_type' => 'concrete_supplier',
            'is_primary' => true,
            'contract_number' => 'CO-2026-0512',
            'notes' => '4000 PSI with integral color (Harvest Gold). Night delivery required.',
            'created_by' => 1,
        ]);

        // Project 3: Commercial pour - Turner Construction
        $project3 = Project::create([
            'company_id' => 1, // Turner Construction
            'name' => 'Ohio State Medical Center - Building C',
            'project_number' => 'PRJ-2026-003',
            'status' => 'active',
            'project_type' => 'commercial',
            'specification_template_id' => 3,
            'address_line1' => '460 W 10th Avenue',
            'city' => 'Columbus',
            'state' => 'OH',
            'zip' => '43210',
            'start_date' => '2026-01-15',
            'end_date' => '2026-12-31',
            'description' => 'Multi-phase structural concrete for new 6-story medical building. Elevated decks, shear walls, columns. Approx 8,500 CY total.',
            'notes' => 'University campus - strict noise ordinances and parking restrictions. Safety orientation required for all crew.',
            'created_by' => 1,
        ]);

        ProjectPhase::create([
            'project_id' => $project3->id,
            'name' => 'Foundations & Below Grade',
            'phase_number' => 1,
            'status' => 'completed',
            'estimated_start_date' => '2026-01-15',
            'estimated_end_date' => '2026-03-01',
            'start_date' => '2026-01-20',
            'end_date' => '2026-03-05',
            'estimated_hours' => 960,
            'actual_hours' => 1040,
            'completion_percentage' => 100,
            'crew_size_estimate' => 16,
            'notes' => 'Weather delays added 4 days.',
            'completed_at' => '2026-03-05',
            'created_by' => 1,
        ]);

        ProjectPhase::create([
            'project_id' => $project3->id,
            'name' => 'Level 1 Elevated Deck',
            'phase_number' => 2,
            'status' => 'in_progress',
            'estimated_start_date' => '2026-03-06',
            'estimated_end_date' => '2026-04-15',
            'start_date' => '2026-03-08',
            'estimated_hours' => 720,
            'actual_hours' => 120,
            'completion_percentage' => 20,
            'crew_size_estimate' => 14,
            'equipment_needs' => json_encode(['tower_crane', 'concrete_pump', 'shoring_system']),
            'created_by' => 1,
        ]);

        ProjectPhase::create([
            'project_id' => $project3->id,
            'name' => 'Levels 2-3 Elevated Decks',
            'phase_number' => 3,
            'status' => 'pending',
            'estimated_start_date' => '2026-04-16',
            'estimated_end_date' => '2026-07-15',
            'estimated_hours' => 1440,
            'completion_percentage' => 0,
            'crew_size_estimate' => 14,
            'created_by' => 1,
        ]);

        ProjectPhase::create([
            'project_id' => $project3->id,
            'name' => 'Levels 4-6 Elevated Decks',
            'phase_number' => 4,
            'status' => 'pending',
            'estimated_start_date' => '2026-07-16',
            'estimated_end_date' => '2026-10-31',
            'estimated_hours' => 1440,
            'completion_percentage' => 0,
            'crew_size_estimate' => 14,
            'created_by' => 1,
        ]);

        ProjectPhase::create([
            'project_id' => $project3->id,
            'name' => 'Roof Deck & Punch List',
            'phase_number' => 5,
            'status' => 'pending',
            'estimated_start_date' => '2026-11-01',
            'estimated_end_date' => '2026-12-31',
            'estimated_hours' => 480,
            'completion_percentage' => 0,
            'crew_size_estimate' => 10,
            'created_by' => 1,
        ]);

        ProjectContact::create([
            'project_id' => $project3->id,
            'contact_id' => 1, // Mike Reynolds - Turner
            'role' => 'superintendent',
            'is_primary' => true,
            'created_by' => 1,
        ]);

        ProjectContact::create([
            'project_id' => $project3->id,
            'contact_id' => 2, // Sarah Chen - Turner
            'role' => 'project_manager',
            'is_primary' => true,
            'created_by' => 1,
        ]);

        ProjectVendor::create([
            'project_id' => $project3->id,
            'company_id' => 3, // Central Ohio Concrete Supply
            'vendor_type' => 'concrete_supplier',
            'is_primary' => true,
            'contract_number' => 'CO-2026-0389',
            'created_by' => 1,
        ]);

        ProjectVendor::create([
            'project_id' => $project3->id,
            'company_id' => 4, // Buckeye Rebar & Steel
            'vendor_type' => 'rebar_supplier',
            'is_primary' => true,
            'contract_number' => 'BR-2026-098',
            'created_by' => 1,
        ]);

        ProjectVendor::create([
            'project_id' => $project3->id,
            'company_id' => 5, // SafeFirst Environmental
            'vendor_type' => 'subcontractor',
            'is_primary' => false,
            'notes' => 'Environmental testing and compliance monitoring.',
            'created_by' => 1,
        ]);

        // Project 4: Completed project
        $project4 = Project::create([
            'company_id' => 2, // Messer Construction
            'name' => 'Polaris Fashion Place Loading Dock',
            'project_number' => 'PRJ-2025-018',
            'status' => 'completed',
            'project_type' => 'flatwork',
            'specification_template_id' => 2,
            'address_line1' => '1500 Polaris Parkway',
            'city' => 'Columbus',
            'state' => 'OH',
            'zip' => '43240',
            'start_date' => '2025-10-01',
            'end_date' => '2025-12-20',
            'completed_at' => '2025-12-18',
            'description' => 'Replace and expand loading dock area. 6,000 SF heavy-duty concrete with embedded steel plates.',
            'created_by' => 1,
        ]);

        ProjectPhase::create([
            'project_id' => $project4->id,
            'name' => 'Demo & Excavation',
            'phase_number' => 1,
            'status' => 'completed',
            'estimated_start_date' => '2025-10-01',
            'estimated_end_date' => '2025-10-15',
            'start_date' => '2025-10-01',
            'end_date' => '2025-10-14',
            'estimated_hours' => 160,
            'actual_hours' => 148,
            'completion_percentage' => 100,
            'completed_at' => '2025-10-14',
            'created_by' => 1,
        ]);

        ProjectPhase::create([
            'project_id' => $project4->id,
            'name' => 'Pour & Finish',
            'phase_number' => 2,
            'status' => 'completed',
            'estimated_start_date' => '2025-10-16',
            'estimated_end_date' => '2025-12-01',
            'start_date' => '2025-10-16',
            'end_date' => '2025-11-28',
            'estimated_hours' => 480,
            'actual_hours' => 440,
            'completion_percentage' => 100,
            'completed_at' => '2025-11-28',
            'created_by' => 1,
        ]);

        ProjectPhase::create([
            'project_id' => $project4->id,
            'name' => 'Curing & Punch List',
            'phase_number' => 3,
            'status' => 'completed',
            'estimated_start_date' => '2025-12-01',
            'estimated_end_date' => '2025-12-20',
            'start_date' => '2025-11-29',
            'end_date' => '2025-12-18',
            'estimated_hours' => 80,
            'actual_hours' => 72,
            'completion_percentage' => 100,
            'completed_at' => '2025-12-18',
            'created_by' => 1,
        ]);

        // Project 5: Upcoming/bidding project
        $project5 = Project::create([
            'company_id' => 1, // Turner Construction
            'name' => 'Nationwide Arena Concourse Renovation',
            'project_number' => 'PRJ-2026-004',
            'status' => 'inactive',
            'project_type' => 'commercial',
            'specification_template_id' => 3,
            'address_line1' => '200 W Nationwide Blvd',
            'city' => 'Columbus',
            'state' => 'OH',
            'zip' => '43215',
            'start_date' => '2026-06-01',
            'end_date' => '2026-09-15',
            'description' => 'Concourse level concrete replacement during off-season. Polished concrete floors, new ADA ramps, and vendor pad areas.',
            'notes' => 'Work window is limited to NHL off-season. Must be complete before September.',
            'created_by' => 1,
        ]);

        ProjectContact::create([
            'project_id' => $project5->id,
            'contact_id' => 3, // Dave Kowalski - Turner
            'role' => 'estimator',
            'is_primary' => true,
            'notes' => 'Lead estimator for bid package.',
            'created_by' => 1,
        ]);

        echo "Projects seeded successfully!\n";
    }
}
