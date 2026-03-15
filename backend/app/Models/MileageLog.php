<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MileageLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vehicle_id',
        'driver_user_id',
        'trip_date',
        'start_odometer',
        'end_odometer',
        'distance_miles',
        'trip_type',
        'from_location',
        'to_location',
        'project_id',
        'purpose',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'trip_date' => 'date',
        'start_odometer' => 'decimal:1',
        'end_odometer' => 'decimal:1',
        'distance_miles' => 'decimal:1',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
