<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'status',
        'is_adhoc',
        'priority',
        'equipment_id',
        'pickup_location_type',
        'pickup_location_id',
        'dropoff_location_type',
        'dropoff_location_id',
        'assigned_driver_id',
        'assigned_vehicle_id',
        'scheduled_date',
        'scheduled_time',
        'special_instructions',
        'requested_by',
        'pickup_movement_id',
        'dropoff_movement_id',
        'picked_up_at',
        'delivered_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_adhoc' => 'boolean',
        'scheduled_date' => 'date',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Relationships

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function assignedDriver()
    {
        return $this->belongsTo(User::class, 'assigned_driver_id');
    }

    public function assignedVehicle()
    {
        return $this->belongsTo(Vehicle::class, 'assigned_vehicle_id');
    }

    public function requestedByUser()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function pickupMovement()
    {
        return $this->belongsTo(EquipmentMovement::class, 'pickup_movement_id');
    }

    public function dropoffMovement()
    {
        return $this->belongsTo(EquipmentMovement::class, 'dropoff_movement_id');
    }

    public function pickupProject()
    {
        return $this->belongsTo(Project::class, 'pickup_location_id')
            ->where($this->getTable() . '.pickup_location_type', 'project');
    }

    public function pickupYard()
    {
        return $this->belongsTo(Yard::class, 'pickup_location_id')
            ->where($this->getTable() . '.pickup_location_type', 'yard');
    }

    public function dropoffProject()
    {
        return $this->belongsTo(Project::class, 'dropoff_location_id')
            ->where($this->getTable() . '.dropoff_location_type', 'project');
    }

    public function dropoffYard()
    {
        return $this->belongsTo(Yard::class, 'dropoff_location_id')
            ->where($this->getTable() . '.dropoff_location_type', 'yard');
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

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForDriver($query, int $userId)
    {
        return $query->where('assigned_driver_id', $userId);
    }

    public function scopeForDate($query, string $date)
    {
        return $query->where('scheduled_date', $date);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['requested', 'assigned']);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'picked_up');
    }

    public function scopeAdhoc($query)
    {
        return $query->where('is_adhoc', true);
    }
}
