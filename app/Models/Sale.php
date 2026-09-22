<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = ['product_id', 'sales_channel_id', 'product_name', 'quantity', 'unit_price', 'shipping_income', 'fee', 'sold_at', 'notes'];

    protected $casts = ['unit_price' => 'decimal:2', 'shipping_income' => 'decimal:2', 'fee' => 'decimal:2', 'sold_at' => 'date'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function channel()
    {
        return $this->belongsTo(SalesChannel::class, 'sales_channel_id');
    }

    public function getNetTotalAttribute(): float
    {
        return $this->net_total_cents / 100;
    }

    public function getGrossTotalAttribute(): float
    {
        return $this->gross_total_cents / 100;
    }

    public function getGrossTotalCentsAttribute(): int
    {
        return Money::cents($this->unit_price) * $this->quantity;
    }

    public function getNetTotalCentsAttribute(): int
    {
        return $this->gross_total_cents + Money::cents($this->shipping_income) - Money::cents($this->fee);
    }
}
