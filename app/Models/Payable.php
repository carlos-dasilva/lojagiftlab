<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payable extends Model
{
    protected $fillable = ['description', 'category', 'amount', 'due_date', 'paid_at', 'notes'];

    protected $casts = ['amount' => 'decimal:2', 'due_date' => 'date', 'paid_at' => 'datetime'];

    public function getIsPaidAttribute(): bool
    {
        return $this->paid_at !== null;
    }
}
