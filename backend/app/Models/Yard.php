<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Yard extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'yard_type',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'zip',
        'gps_latitude',
        'gps_longitude',
        'gps_radius_feet',
        'contact_phone',
        'contact_email',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'gps_latitude' => 'decimal:8',
        'gps_longitude' => 'decimal:8',
        'gps_radius_feet' => 'integer',
        'is_active' => 'boolean',
    ];

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
     * Check if GPS coordinates are within geofence radius
     */
    public function isWithinGeofence(float $latitude, float $longitude): bool
    {
        $earthRadius = 20902231; // feet

        $latFrom = deg2rad($this->gps_latitude);
        $lonFrom = deg2rad($this->gps_longitude);
        $latTo = deg2rad($latitude);
        $lonTo = deg2rad($longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c;

        return $distance <= $this->gps_radius_feet;
    }
}
