<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'from_location_type',
        'from_location_id',
        'from_location_gps_lat',
        'from_location_gps_lng',
        'to_location_type',
        'to_location_id',
        'to_location_gps_lat',
        'to_location_gps_lng',
        'movement_type',
        'odometer_reading',
        'moved_at',
        'moved_by',
        'transported_by_user_id',
        'scanned_via_qr',
        'device_info',
        'notes',
    ];

    protected $casts = [
        'from_location_gps_lat' => 'decimal:8',
        'from_location_gps_lng' => 'decimal:8',
        'to_location_gps_lat' => 'decimal:8',
        'to_location_gps_lng' => 'decimal:8',
        'odometer_reading' => 'decimal:1',
        'moved_at' => 'datetime',
        'scanned_via_qr' => 'boolean',
    ];

    /**
     * Get the vehicle for this movement
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the user who moved the vehicle
     */
    public function movedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moved_by');
    }

    /**
     * Get the user who moved the vehicle (alias)
     */
    public function movedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moved_by');
    }

    /**
     * Get the from project if from_location_type is 'project'
     */
    public function fromProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'from_location_id')
            ->where('from_location_type', 'project');
    }

    /**
     * Get the to project if to_location_type is 'project'
     */
    public function toProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'to_location_id')
            ->where('to_location_type', 'project');
    }

    /**
     * Get the from yard if from_location_type is 'yard'
     */
    public function fromYard(): BelongsTo
    {
        return $this->belongsTo(Yard::class, 'from_location_id')
            ->where('from_location_type', 'yard');
    }

    /**
     * Get the to yard if to_location_type is 'yard'
     */
    public function toYard(): BelongsTo
    {
        return $this->belongsTo(Yard::class, 'to_location_id')
            ->where('to_location_type', 'yard');
    }

    /**
     * Get the user who transported this vehicle
     */
    public function transportedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transported_by_user_id');
    }
}
