<?php

namespace App\Models;

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
        return (float) $this->unit_price * $this->quantity;
    }
}
