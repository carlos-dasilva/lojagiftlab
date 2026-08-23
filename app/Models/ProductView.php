<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductView extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_id', 'session_hash', 'viewed_at'];

    protected $casts = ['viewed_at' => 'datetime'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
