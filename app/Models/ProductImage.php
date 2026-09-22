<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = ['product_id', 'path', 'thumbnail_path', 'catalog_path', 'alt', 'is_primary', 'order'];

    public function files(): array
    {
        return array_values(array_filter([$this->path, $this->thumbnail_path, $this->catalog_path]));
    }

    protected $casts = ['is_primary' => 'boolean'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
