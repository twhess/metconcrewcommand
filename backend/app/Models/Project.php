<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'name', 'project_number', 'status',
        'project_type', 'specification_template_id',
        'address_line1', 'address_line2', 'city', 'state', 'zip',
        'start_date', 'end_date', 'description', 'notes', 'completed_at',
        'created_by', 'updated_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function specificationTemplate()
    {
        return $this->belongsTo(SpecificationTemplate::class, 'specification_template_id');
    }

    public function specifications()
    {
        return $this->hasMany(ProjectSpecification::class)->orderBy('sort_order');
    }

    public function phases()
    {
        return $this->hasMany(ProjectPhase::class)->orderBy('phase_number');
    }

    public function projectContacts()
    {
        return $this->hasMany(ProjectContact::class);
    }

    public function projectVendors()
    {
        return $this->hasMany(ProjectVendor::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('project_type', $type);
    }

    public function scopeByTemplate($query, int $templateId)
    {
        return $query->where('specification_template_id', $templateId);
    }

    // Methods
    public function applyTemplate(SpecificationTemplate $template): void
    {
        $this->update(['specification_template_id' => $template->id]);
        $template->applyToProject($this);
    }

    public function getCompletionPercentage(): float
    {
        $totalPhases = $this->phases()->count();
        if ($totalPhases === 0) {
            return 0.0;
        }

        $totalCompletion = $this->phases()->sum('completion_percentage');
        return round($totalCompletion / $totalPhases, 2);
    }

    public function getPrimaryContact(string $role): ?Contact
    {
        $projectContact = $this->projectContacts()
            ->where('role', $role)
            ->where('is_primary', true)
            ->with('contact')
            ->first();

        return $projectContact?->contact;
    }

    public function getPrimaryVendor(string $type): ?Company
    {
        $projectVendor = $this->projectVendors()
            ->where('vendor_type', $type)
            ->where('is_primary', true)
            ->with('company')
            ->first();

        return $projectVendor?->company;
    }

    public function markComplete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
