<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;

class CreditSaleItem extends Model
{
    protected $fillable = ['product_id', 'item_name', 'quantity', 'unit_price', 'order'];

    protected $casts = ['quantity' => 'integer', 'unit_price' => 'decimal:2', 'order' => 'integer'];

    public function creditSale()
    {
        return $this->belongsTo(CreditSale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getTotalAttribute(): float
    {
        return $this->total_cents / 100;
    }

    public function getTotalCentsAttribute(): int
    {
        return Money::cents($this->unit_price) * $this->quantity;
    }
}
