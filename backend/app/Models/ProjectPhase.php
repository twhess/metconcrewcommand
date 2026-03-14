<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectPhase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'phase_number',
        'status',
        'start_date',
        'end_date',
        'estimated_start_date',
        'estimated_end_date',
        'estimated_hours',
        'actual_hours',
        'completion_percentage',
        'equipment_needs',
        'crew_size_estimate',
        'notes',
        'completed_at',
        'completed_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'estimated_start_date' => 'date',
        'estimated_end_date' => 'date',
        'estimated_hours' => 'decimal:1',
        'actual_hours' => 'decimal:1',
        'completion_percentage' => 'integer',
        'equipment_needs' => 'array',
        'crew_size_estimate' => 'integer',
        'completed_at' => 'datetime',
        'phase_number' => 'integer',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('phase_number');
    }

    // Methods
    public function markComplete(): void
    {
        $this->update([
            'status' => 'completed',
            'completion_percentage' => 100,
            'completed_at' => now(),
            'completed_by' => auth()->id(),
        ]);
    }

    public function calculateActualHours(): float
    {
        // Future enhancement: calculate from linked schedules
        return $this->actual_hours ?? 0.0;
    }
}
