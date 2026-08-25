<?php

namespace App\Models;

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
        return ((float) $this->unit_price * $this->quantity) + (float) $this->shipping_income - (float) $this->fee;
    }
}
