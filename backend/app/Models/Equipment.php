<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'equipment_number',
        'qr_code',
        'type',
        'category',
        'status',
        'current_location_type',
        'current_location_id',
        'current_location_gps_lat',
        'current_location_gps_lng',
        'has_hour_meter',
        'current_hours',
        'last_hours_reading_at',
        'description',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'current_location_gps_lat' => 'decimal:8',
        'current_location_gps_lng' => 'decimal:8',
        'has_hour_meter' => 'boolean',
        'current_hours' => 'decimal:1',
        'last_hours_reading_at' => 'datetime',
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

    public function currentYard()
    {
        return $this->belongsTo(Yard::class, 'current_location_id')
            ->where('current_location_type', 'yard');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get all maintenance records for this equipment
     */
    public function maintenanceRecords()
    {
        return $this->morphMany(MaintenanceRecord::class, 'maintainable')->orderBy('performed_at', 'desc');
    }

    /**
     * Get all maintenance schedules for this equipment
     */
    public function maintenanceSchedules()
    {
        return $this->morphMany(MaintenanceSchedule::class, 'maintainable')->where('is_active', true);
    }

    /**
     * Get the latest maintenance record
     */
    public function latestMaintenance()
    {
        return $this->morphOne(MaintenanceRecord::class, 'maintainable')->latestOfMany('performed_at');
    }

    /**
     * Check if equipment is currently in transit
     */
    public function isInTransit(): bool
    {
        return $this->status === 'in_transit' || $this->current_location_type === 'in_transit';
    }
}
