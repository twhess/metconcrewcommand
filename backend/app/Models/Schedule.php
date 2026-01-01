<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id', 'date', 'start_time', 'end_time', 'status',
        'dispatch_instructions', 'notes'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function crewAssignments()
    {
        return $this->hasMany(CrewAssignment::class);
    }

    public function crew()
    {
        return $this->belongsToMany(User::class, 'crew_assignments');
    }

    public function foreman()
    {
        return $this->hasOneThrough(
            User::class,
            CrewAssignment::class,
            'schedule_id',
            'id',
            'id',
            'user_id'
        )->where('is_foreman', true);
    }

    public function equipmentAssignments()
    {
        return $this->hasMany(EquipmentAssignment::class);
    }

    public function equipment()
    {
        return $this->belongsToMany(Equipment::class, 'equipment_assignments');
    }

    public function materials()
    {
        return $this->hasMany(Material::class);
    }
}
