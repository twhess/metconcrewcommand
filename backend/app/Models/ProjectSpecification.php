<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectSpecification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'category',
        'requirement_name',
        'requirement_description',
        'is_required',
        'value',
        'compliance_status',
        'compliance_notes',
        'verified_by',
        'verified_at',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'value' => 'array',
        'verified_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
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
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByComplianceStatus($query, string $status)
    {
        return $query->where('compliance_status', $status);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }
}
