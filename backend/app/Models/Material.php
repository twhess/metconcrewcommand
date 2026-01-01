<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id', 'type', 'quantity', 'unit', 'rate_per_hour',
        'additives', 'dispatch_number', 'special_instructions'
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}
