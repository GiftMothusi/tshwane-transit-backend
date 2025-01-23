<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusRoute extends Model
{
    protected $fillable = [
        'route_number',
        'name',
        'description',
        'stops',
        'fare',
        'is_express',
        'estimated_duration'
    ];

    protected $casts = [
        'stops' => 'array',
        'fare' => 'decimal:2',
        'is_express' => 'boolean',
        'estimated_duration' => 'integer'
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(BusSchedule::class, 'route_id');
    }
}
