<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpecificationTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'project_type',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function items(): HasMany
    {
        return $this->hasMany(SpecificationTemplateItem::class)->orderBy('sort_order');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'specification_template_id');
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
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByProjectType($query, string $type)
    {
        return $query->where('project_type', $type);
    }

    // Methods
    public function duplicate(string $newName, string $newSlug): self
    {
        $duplicate = $this->replicate();
        $duplicate->name = $newName;
        $duplicate->slug = $newSlug;
        $duplicate->created_by = auth()->id();
        $duplicate->updated_by = auth()->id();
        $duplicate->save();

        // Duplicate items
        foreach ($this->items as $item) {
            $duplicateItem = $item->replicate();
            $duplicateItem->specification_template_id = $duplicate->id;
            $duplicateItem->created_by = auth()->id();
            $duplicateItem->updated_by = auth()->id();
            $duplicateItem->save();
        }

        return $duplicate->load('items');
    }

    public function applyToProject(Project $project): void
    {
        foreach ($this->items as $item) {
            ProjectSpecification::create([
                'project_id' => $project->id,
                'category' => $item->category,
                'requirement_name' => $item->requirement_name,
                'requirement_description' => $item->requirement_description,
                'is_required' => $item->is_required,
                'value' => $item->default_value,
                'compliance_status' => 'not_started',
                'sort_order' => $item->sort_order,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }
    }
}
