<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipmentAssignment extends Model
{
    use HasFactory;

    protected $fillable = ['schedule_id', 'equipment_id'];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
