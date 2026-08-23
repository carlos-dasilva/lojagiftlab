<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSalesLink extends Model
{
    protected $fillable = ['product_id', 'sales_channel_id', 'url', 'price', 'label', 'message', 'order', 'active'];

    protected $casts = ['price' => 'decimal:2', 'active' => 'boolean'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function channel()
    {
        return $this->belongsTo(SalesChannel::class, 'sales_channel_id');
    }
}
