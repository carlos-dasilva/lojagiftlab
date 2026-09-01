<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditSale extends Model
{
    protected $fillable = ['product_id', 'sales_channel_id', 'product_name', 'customer_name', 'customer_contact', 'quantity', 'unit_price', 'shipping_income', 'fee', 'sold_at', 'due_date', 'delivered_at', 'received_at', 'notes'];

    protected $casts = ['quantity' => 'integer', 'unit_price' => 'decimal:2', 'shipping_income' => 'decimal:2', 'fee' => 'decimal:2', 'sold_at' => 'date', 'due_date' => 'date', 'delivered_at' => 'datetime', 'received_at' => 'datetime'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function channel()
    {
        return $this->belongsTo(SalesChannel::class, 'sales_channel_id');
    }

    public function items()
    {
        return $this->hasMany(CreditSaleItem::class)->orderBy('order');
    }

    public function getGrossTotalAttribute(): float
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        return $items->isNotEmpty() ? (float) $items->sum('total') : (float) $this->unit_price * $this->quantity;
    }

    public function getNetTotalAttribute(): float
    {
        return $this->gross_total + (float) $this->shipping_income - (float) $this->fee;
    }

    public function getIsReceivedAttribute(): bool
    {
        return $this->received_at !== null;
    }

    public function getIsDeliveredAttribute(): bool
    {
        return $this->delivered_at !== null;
    }
}
