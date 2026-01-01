<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrewAssignment extends Model
{
    use HasFactory;

    protected $fillable = ['schedule_id', 'user_id', 'is_foreman'];

    protected $casts = [
        'is_foreman' => 'boolean',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
