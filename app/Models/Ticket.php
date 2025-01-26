<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Ticket extends Model
{
    protected $fillable = [
        'route_id',
        'valid_from',
        'valid_until',
        'status',
        'qr_code',
        'metadata'
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'metadata' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(BusRoute::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public static function generateQRCode(): string
    {
        return Str::random(32);
    }

    public function isValid(): bool
    {
        return $this->status === 'active' &&
               now()->between($this->valid_from, $this->valid_until);
    }
}
