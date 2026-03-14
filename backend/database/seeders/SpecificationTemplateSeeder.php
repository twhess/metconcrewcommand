<?php

namespace Database\Seeders;

use App\Models\SpecificationTemplate;
use Illuminate\Database\Seeder;

class SpecificationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Foundation Work Template
        $foundation = SpecificationTemplate::create([
            'name' => 'Foundation Work',
            'slug' => 'foundation-work',
            'project_type' => 'foundation',
            'description' => 'Standard requirements for foundation and concrete work projects',
            'is_active' => true,
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        $foundation->items()->createMany([
            [
                'category' => 'safety',
                'requirement_name' => 'Fall Protection Plan',
                'requirement_description' => 'Comprehensive fall protection plan for excavation and foundation work',
                'is_required' => true,
                'sort_order' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'category' => 'safety',
                'requirement_name' => 'Excavation Safety',
                'requirement_description' => 'OSHA compliant excavation and trenching safety measures',
                'is_required' => true,
                'sort_order' => 2,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'category' => 'regulatory',
                'requirement_name' => 'Building Permit',
                'requirement_description' => 'Valid building permit from local jurisdiction',
                'is_required' => true,
                'sort_order' => 3,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'category' => 'regulatory',
                'requirement_name' => 'Soil Testing Report',
                'requirement_description' => 'Geotechnical soil testing and compaction report',
                'is_required' => true,
                'sort_order' => 4,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'category' => 'insurance',
                'requirement_name' => 'General Liability Insurance',
                'requirement_description' => 'Minimum $2M general liability coverage',
                'is_required' => true,
                'sort_order' => 5,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'category' => 'prevailing_wage',
                'requirement_name' => 'Prevailing Wage Compliance',
                'requirement_description' => 'Davis-Bacon or state prevailing wage requirements if applicable',
                'is_required' => false,
                'sort_order' => 6,
                'created_by' => 1,
                'updated_by' => 1,
            ],
        ]);

        // Flatwork Template
        $flatwork = SpecificationTemplate::create([
            'name' => 'Flatwork & Paving',
            'slug' => 'flatwork-paving',
            'project_type' => 'flatwork',
            'description' => 'Requirements for concrete flatwork, slabs, and paving projects',
            'is_active' => true,
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        $flatwork->items()->createMany([
            [
                'category' => 'safety',
                'requirement_name' => 'Traffic Control Plan',
                'requirement_description' => 'MUTCD compliant traffic control plan if working near roadways',
                'is_required' => true,
                'sort_order' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'category' => 'safety',
                'requirement_name' => 'PPE Requirements',
                'requirement_description' => 'Hard hats, safety glasses, steel-toed boots, high-visibility vests',
                'is_required' => true,
                'sort_order' => 2,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'category' => 'regulatory',
                'requirement_name' => 'Grading Permit',
                'requirement_description' => 'Site grading and drainage permit if required',
                'is_required' => false,
                'sort_order' => 3,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'category' => 'regulatory',
                'requirement_name' => 'ADA Compliance',
                'requirement_description' => 'Verify compliance with ADA accessibility standards',
                'is_required' => true,
                'sort_order' => 4,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'category' => 'insurance',
                'requirement_name' => 'Auto Insurance',
                'requirement_description' => 'Commercial auto insurance for all vehicles and equipment',
                'is_required' => true,
                'sort_order' => 5,
                'created_by' => 1,
                'updated_by' => 1,
            ],
        ]);

        // Commercial Pour Template
        $commercial = SpecificationTemplate::create([
            'name' => 'Commercial Pour',
            'slug' => 'commercial-pour',
            'project_type' => 'commercial',
            'description' => 'Requirements for large-scale commercial concrete pours',
            'is_active' => true,
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        $commercial->items()->createMany([
            [
                'category' => 'safety',
                'requirement_name' => 'Site-Specific Safety Plan',
                'requirement_description' => 'Comprehensive safety plan reviewed with all crew members',
                'is_required' => true,
                'sort_order' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'category' => 'safety',
                'requirement_name' => 'Crane Safety',
                'requirement_description' => 'Certified crane operator and lift plan if using crane',
                'is_required' => false,
                'sort_order' => 2,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'category' => 'regulatory',
                'requirement_name' => 'Commercial Building Permit',
                'requirement_description' => 'Valid commercial construction permit',
                'is_required' => true,
                'sort_order' => 3,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'category' => 'regulatory',
                'requirement_name' => 'Concrete Testing',
                'requirement_description' => 'Third-party concrete testing and slump verification',
                'is_required' => true,
                'sort_order' => 4,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'category' => 'tax',
                'requirement_name' => 'Sales Tax Exemption',
                'requirement_description' => 'Verify tax exempt status if applicable to commercial project',
                'is_required' => false,
                'sort_order' => 5,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'category' => 'prevailing_wage',
                'requirement_name' => 'Certified Payroll',
                'requirement_description' => 'Weekly certified payroll reports for prevailing wage projects',
                'is_required' => false,
                'sort_order' => 6,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'category' => 'insurance',
                'requirement_name' => 'Workers Compensation',
                'requirement_description' => 'Current workers compensation insurance certificate',
                'is_required' => true,
                'sort_order' => 7,
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'category' => 'insurance',
                'requirement_name' => 'Performance Bond',
                'requirement_description' => 'Performance and payment bond if required by contract',
                'is_required' => false,
                'sort_order' => 8,
                'created_by' => 1,
                'updated_by' => 1,
            ],
        ]);
    }
}
