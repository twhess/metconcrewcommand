<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipmentMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipment_id', 'from_location_type', 'from_location_id',
        'to_location_type', 'to_location_id', 'moved_at', 'moved_by', 'notes'
    ];

    protected $casts = [
        'moved_at' => 'datetime',
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
}
