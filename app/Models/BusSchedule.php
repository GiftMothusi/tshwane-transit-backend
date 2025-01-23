<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusSchedule extends Model
{
    protected $fillable = [
        'route_id',
        'departure_time',
        'day_type',
        'is_active',
        'bus_number',
        'capacity',
        'current_location'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capacity' => 'integer',
        'current_location' => 'array',
        'departure_time' => 'datetime'
    ];

    public function route()
    {
        return $this->belongsTo(BusRoute::class);
    }
}
