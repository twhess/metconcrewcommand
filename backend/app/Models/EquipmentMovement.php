<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipmentMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipment_id',
        'from_location_type',
        'from_location_id',
        'from_location_gps_lat',
        'from_location_gps_lng',
        'to_location_type',
        'to_location_id',
        'to_location_gps_lat',
        'to_location_gps_lng',
        'movement_type',
        'hours_reading',
        'moved_at',
        'moved_by',
        'transported_by_user_id',
        'transport_vehicle_id',
        'temp_transport_vehicle',
        'is_rental',
        'rental_company',
        'rental_agreement_number',
        'scanned_via_qr',
        'device_info',
        'notes',
    ];

    protected $casts = [
        'from_location_gps_lat' => 'decimal:8',
        'from_location_gps_lng' => 'decimal:8',
        'to_location_gps_lat' => 'decimal:8',
        'to_location_gps_lng' => 'decimal:8',
        'hours_reading' => 'decimal:1',
        'moved_at' => 'datetime',
        'scanned_via_qr' => 'boolean',
        'is_rental' => 'boolean',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function mover()
    {
        return $this->belongsTo(User::class, 'moved_by');
    }

    public function movedByUser()
    {
        return $this->belongsTo(User::class, 'moved_by');
    }

    public function fromProject()
    {
        return $this->belongsTo(Project::class, 'from_location_id')
            ->where('from_location_type', 'project');
    }

    public function toProject()
    {
        return $this->belongsTo(Project::class, 'to_location_id')
            ->where('to_location_type', 'project');
    }

    public function fromYard()
    {
        return $this->belongsTo(Yard::class, 'from_location_id')
            ->where('from_location_type', 'yard');
    }

    public function toYard()
    {
        return $this->belongsTo(Yard::class, 'to_location_id')
            ->where('to_location_type', 'yard');
    }

    public function transportedByUser()
    {
        return $this->belongsTo(User::class, 'transported_by_user_id');
    }

    public function transportVehicle()
    {
        return $this->belongsTo(Vehicle::class, 'transport_vehicle_id');
    }
}
