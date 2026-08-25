<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payable extends Model
{
    protected $fillable = ['installment_group', 'description', 'category', 'amount', 'installment_number', 'installments_total', 'due_date', 'paid_at', 'notes'];

    protected $casts = ['amount' => 'decimal:2', 'installment_number' => 'integer', 'installments_total' => 'integer', 'due_date' => 'date', 'paid_at' => 'datetime'];

    public function getIsPaidAttribute(): bool
    {
        return $this->paid_at !== null;
    }
}
