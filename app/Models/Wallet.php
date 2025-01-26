<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = ['balance', 'currency'];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function canAfford(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    public function addFunds(float $amount): void
    {
        $this->increment('balance', $amount);
    }

    public function deductFunds(float $amount): bool
    {
        if (!$this->canAfford($amount)) {
            return false;
        }

        $this->decrement('balance', $amount);
        return true;
    }
}
