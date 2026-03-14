<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'type', 'project_id', 'vehicle_id', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function stock()
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function stocks()
    {
        return $this->hasMany(InventoryStock::class);
    }

    // Scopes
    public function scopeForVehicle($query, $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopeShopLocations($query)
    {
        return $query->whereIn('type', ['warehouse', 'yard']);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
