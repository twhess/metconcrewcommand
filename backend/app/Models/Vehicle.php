<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'vehicle_number',
        'qr_code',
        'vin',
        'license_plate',
        'registration_state',
        'registration_expiration',
        'year',
        'make',
        'model',
        'color',
        'vehicle_type',
        'fuel_type',
        'tank_capacity_gallons',
        'weight_class',
        'gvwr_pounds',
        'towing_capacity_pounds',
        'current_odometer_miles',
        'last_odometer_reading_at',
        'requires_cdl',
        'requires_dot_inspection',
        'last_dot_inspection_date',
        'next_dot_inspection_due',
        'insurance_policy_number',
        'insurance_provider',
        'insurance_expiration',
        'status',
        'current_location_type',
        'current_location_id',
        'current_location_gps_lat',
        'current_location_gps_lng',
        'assigned_to_user_id',
        'description',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'registration_expiration' => 'date',
        'year' => 'integer',
        'tank_capacity_gallons' => 'decimal:2',
        'gvwr_pounds' => 'integer',
        'towing_capacity_pounds' => 'integer',
        'current_odometer_miles' => 'decimal:1',
        'last_odometer_reading_at' => 'datetime',
        'requires_cdl' => 'boolean',
        'requires_dot_inspection' => 'boolean',
        'last_dot_inspection_date' => 'date',
        'next_dot_inspection_due' => 'date',
        'insurance_expiration' => 'date',
        'current_location_gps_lat' => 'decimal:8',
        'current_location_gps_lng' => 'decimal:8',
    ];

    /**
     * Get the user who is assigned to this vehicle
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Get all movements for this vehicle
     */
    public function movements(): HasMany
    {
        return $this->hasMany(VehicleMovement::class);
    }

    /**
     * Get the latest movement for this vehicle
     */
    public function latestMovement(): HasMany
    {
        return $this->hasMany(VehicleMovement::class)->latestOfMany('moved_at');
    }

    /**
     * Get the user who created this record
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the current project if location type is 'project'
     */
    public function currentProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'current_location_id')
            ->where('current_location_type', 'project');
    }

    /**
     * Get the current yard if location type is 'yard'
     */
    public function currentYard(): BelongsTo
    {
        return $this->belongsTo(Yard::class, 'current_location_id')
            ->where('current_location_type', 'yard');
    }

    /**
     * Get all maintenance records for this vehicle
     */
    public function maintenanceRecords()
    {
        return $this->morphMany(MaintenanceRecord::class, 'maintainable')->orderBy('performed_at', 'desc');
    }

    /**
     * Get all maintenance schedules for this vehicle
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
     * Check if vehicle is currently in transit
     */
    public function isInTransit(): bool
    {
        return $this->status === 'in_transit';
    }

    /**
     * Check if DOT inspection is due soon (within 30 days)
     */
    public function isDotInspectionDueSoon(): bool
    {
        if (!$this->requires_dot_inspection || !$this->next_dot_inspection_due) {
            return false;
        }

        return $this->next_dot_inspection_due->diffInDays(now()) <= 30;
    }

    /**
     * Check if insurance is expiring soon (within 30 days)
     */
    public function isInsuranceExpiringSoon(): bool
    {
        if (!$this->insurance_expiration) {
            return false;
        }

        return $this->insurance_expiration->diffInDays(now()) <= 30;
    }

    /**
     * Check if registration is expiring soon (within 30 days)
     */
    public function isRegistrationExpiringSoon(): bool
    {
        if (!$this->registration_expiration) {
            return false;
        }

        return $this->registration_expiration->diffInDays(now()) <= 30;
    }
}
