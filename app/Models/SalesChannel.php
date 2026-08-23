<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesChannel extends Model
{
    protected $fillable = ['name', 'slug', 'icon', 'color', 'base_url', 'active', 'order'];

    protected $casts = ['active' => 'boolean'];

    public function links()
    {
        return $this->hasMany(ProductSalesLink::class);
    }
}
