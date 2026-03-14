<?php

namespace App\Services;

use App\Models\Project;
use App\Models\SpecificationTemplate;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    /**
     * Create a new project and optionally apply a specification template.
     */
    public function createProjectFromTemplate(array $projectData, ?int $templateId = null): Project
    {
        return DB::transaction(function () use ($projectData, $templateId) {
            // Create the project
            $project = Project::create($projectData);

            // Apply template if provided
            if ($templateId) {
                $template = SpecificationTemplate::with('items')->findOrFail($templateId);
                $template->applyToProject($project);

                // Link the template to the project
                $project->update(['specification_template_id' => $templateId]);
            }

            return $project->load(['specifications', 'phases', 'projectContacts', 'projectVendors']);
        });
    }

    /**
     * Apply a specification template to an existing project.
     */
    public function applyTemplateToProject(int $projectId, int $templateId): Project
    {
        return DB::transaction(function () use ($projectId, $templateId) {
            $project = Project::findOrFail($projectId);
            $template = SpecificationTemplate::with('items')->findOrFail($templateId);

            $template->applyToProject($project);

            $project->update(['specification_template_id' => $templateId]);

            return $project->load('specifications');
        });
    }

    /**
     * Calculate overall project completion percentage based on phases.
     */
    public function calculateProjectCompletion(int $projectId): float
    {
        $project = Project::findOrFail($projectId);

        return $project->getCompletionPercentage();
    }

    /**
     * Get comprehensive project dashboard data.
     */
    public function getProjectDashboard(int $projectId): array
    {
        $project = Project::with([
            'company',
            'specifications.verifiedBy',
            'phases.completedBy',
            'projectContacts.contact.company',
            'projectVendors.company',
            'specificationTemplate',
        ])->findOrFail($projectId);

        // Specification summary
        $specifications = $project->specifications;
        $specSummary = [
            'total' => $specifications->count(),
            'required' => $specifications->where('is_required', true)->count(),
            'compliant' => $specifications->where('compliance_status', 'compliant')->count(),
            'non_compliant' => $specifications->where('compliance_status', 'non_compliant')->count(),
            'in_progress' => $specifications->where('compliance_status', 'in_progress')->count(),
            'not_started' => $specifications->where('compliance_status', 'not_started')->count(),
            'verified' => $specifications->whereNotNull('verified_at')->count(),
        ];

        // Phase summary
        $phases = $project->phases;
        $phaseSummary = [
            'total' => $phases->count(),
            'pending' => $phases->where('status', 'pending')->count(),
            'in_progress' => $phases->where('status', 'in_progress')->count(),
            'completed' => $phases->where('status', 'completed')->count(),
            'on_hold' => $phases->where('status', 'on_hold')->count(),
            'overall_completion' => $project->getCompletionPercentage(),
            'total_estimated_hours' => $phases->sum('estimated_hours'),
            'total_actual_hours' => $phases->sum('actual_hours'),
        ];

        // Contact summary (grouped by role)
        $contactsByRole = $project->projectContacts->groupBy('role')->map(function ($contacts) {
            return [
                'count' => $contacts->count(),
                'primary' => $contacts->where('is_primary', true)->first()?->contact,
                'all' => $contacts->pluck('contact'),
            ];
        });

        // Vendor summary (grouped by type)
        $vendorsByType = $project->projectVendors->where('is_active', true)->groupBy('vendor_type')->map(function ($vendors) {
            return [
                'count' => $vendors->count(),
                'primary' => $vendors->where('is_primary', true)->first()?->company,
                'all' => $vendors->pluck('company'),
            ];
        });

        return [
            'project' => $project,
            'specifications' => [
                'summary' => $specSummary,
                'by_category' => $specifications->groupBy('category'),
            ],
            'phases' => [
                'summary' => $phaseSummary,
                'list' => $phases,
            ],
            'contacts' => [
                'by_role' => $contactsByRole,
                'total' => $project->projectContacts->count(),
            ],
            'vendors' => [
                'by_type' => $vendorsByType,
                'total' => $project->projectVendors->where('is_active', true)->count(),
            ],
        ];
    }

    /**
     * Duplicate a project with all its specifications, phases, and vendors.
     */
    public function duplicateProject(int $projectId, string $newName, string $newProjectNumber): Project
    {
        return DB::transaction(function () use ($projectId, $newName, $newProjectNumber) {
            $originalProject = Project::with([
                'specifications',
                'phases',
                'projectVendors',
            ])->findOrFail($projectId);

            // Create new project
            $newProject = $originalProject->replicate();
            $newProject->name = $newName;
            $newProject->project_number = $newProjectNumber;
            $newProject->status = 'planning';
            $newProject->completed_at = null;
            $newProject->created_by = auth()->id();
            $newProject->updated_by = auth()->id();
            $newProject->save();

            // Duplicate specifications
            foreach ($originalProject->specifications as $spec) {
                $newSpec = $spec->replicate();
                $newSpec->project_id = $newProject->id;
                $newSpec->compliance_status = 'not_started';
                $newSpec->verified_by = null;
                $newSpec->verified_at = null;
                $newSpec->created_by = auth()->id();
                $newSpec->updated_by = auth()->id();
                $newSpec->save();
            }

            // Duplicate phases
            foreach ($originalProject->phases as $phase) {
                $newPhase = $phase->replicate();
                $newPhase->project_id = $newProject->id;
                $newPhase->status = 'pending';
                $newPhase->completion_percentage = 0;
                $newPhase->actual_start_date = null;
                $newPhase->actual_end_date = null;
                $newPhase->actual_hours = null;
                $newPhase->completed_by = null;
                $newPhase->completed_at = null;
                $newPhase->created_by = auth()->id();
                $newPhase->updated_by = auth()->id();
                $newPhase->save();
            }

            // Duplicate vendor assignments
            foreach ($originalProject->projectVendors as $vendor) {
                $newVendor = $vendor->replicate();
                $newVendor->project_id = $newProject->id;
                $newVendor->created_by = auth()->id();
                $newVendor->updated_by = auth()->id();
                $newVendor->save();
            }

            // Note: We don't duplicate contacts as those are typically project-specific assignments

            return $newProject->load(['specifications', 'phases', 'projectVendors']);
        });
    }

    /**
     * Mark a project as complete (validates all phases are complete).
     */
    public function markProjectComplete(int $projectId, array $completionData = []): Project
    {
        return DB::transaction(function () use ($projectId, $completionData) {
            $project = Project::with('phases')->findOrFail($projectId);

            // Validate all phases are complete
            $incompletePhasesCount = $project->phases()
                ->where('status', '!=', 'completed')
                ->count();

            if ($incompletePhasesCount > 0) {
                throw new \Exception("Cannot complete project: {$incompletePhasesCount} phase(s) are not complete");
            }

            $project->update([
                'status' => 'completed',
                'completed_at' => now(),
                'updated_by' => auth()->id(),
            ]);

            return $project;
        });
    }
}
