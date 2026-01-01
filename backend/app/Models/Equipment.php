<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'equipment_number', 'type', 'category', 'status',
        'current_location_type', 'current_location_id', 'description', 'notes'
    ];

    public function equipmentAssignments()
    {
        return $this->hasMany(EquipmentAssignment::class);
    }

    public function movements()
    {
        return $this->hasMany(EquipmentMovement::class);
    }

    public function latestMovement()
    {
        return $this->hasOne(EquipmentMovement::class)->latestOfMany('moved_at');
    }

    public function currentProject()
    {
        return $this->belongsTo(Project::class, 'current_location_id')
            ->where('current_location_type', 'project');
    }
}
