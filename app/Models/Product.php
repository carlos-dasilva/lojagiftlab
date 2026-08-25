<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['category_id', 'name', 'slug', 'sku', 'short_description', 'description', 'cost_price', 'sale_price', 'discount_percentage', 'stock', 'weight_kg', 'width_cm', 'height_cm', 'length_cm', 'condition', 'condition_notes', 'featured', 'is_new', 'customizable', 'made_to_order', 'is_bundle', 'order', 'status'];

    protected $hidden = ['cost_price'];

    protected $casts = ['status' => ProductStatus::class, 'cost_price' => 'decimal:2', 'sale_price' => 'decimal:2', 'discount_percentage' => 'decimal:2', 'weight_kg' => 'decimal:3', 'width_cm' => 'decimal:2', 'height_cm' => 'decimal:2', 'length_cm' => 'decimal:2', 'featured' => 'boolean', 'is_new' => 'boolean', 'customizable' => 'boolean', 'made_to_order' => 'boolean', 'is_bundle' => 'boolean'];

    public function categories()
    {
        return $this->belongsToMany(Category::class)->orderBy('name');
    }

    public function getCategoryAttribute(): ?Category
    {
        return $this->categories->first();
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->orderBy('order');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function attributes()
    {
        return $this->hasMany(ProductAttribute::class)->orderBy('order');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function salesLinks()
    {
        return $this->hasMany(ProductSalesLink::class)->where('active', true)->orderBy('order');
    }

    public function videos()
    {
        return $this->hasMany(ProductVideo::class)->orderBy('order');
    }

    public function bundleItems()
    {
        return $this->belongsToMany(Product::class, 'bundle_product', 'bundle_id', 'component_product_id')->withPivot('quantity')->withTimestamps()->orderBy('name');
    }

    public function containingBundles()
    {
        return $this->belongsToMany(Product::class, 'bundle_product', 'component_product_id', 'bundle_id')->withPivot('quantity');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function getStartingPriceAttribute(): ?string
    {
        $price = $this->relationLoaded('salesLinks')
            ? $this->salesLinks->whereNotNull('price')->min('price')
            : $this->salesLinks()->whereNotNull('price')->min('price');

        return $price === null ? null : number_format((float) $price, 2, '.', '');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function views()
    {
        return $this->hasMany(ProductView::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Published);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('short_description', 'like', "%{$term}%")->orWhereHas('categories', fn ($q) => $q->where('name', 'like', "%{$term}%"))->orWhereHas('tags', fn ($q) => $q->where('name', 'like', "%{$term}%"))));
    }

    public function getFinalPriceAttribute(): string
    {
        return bcsub((string) $this->sale_price, bcmul((string) $this->sale_price, bcdiv((string) $this->discount_percentage, '100', 4), 4), 2);
    }

    public function getProfitAttribute(): string
    {
        return bcsub($this->final_price, (string) $this->cost_price, 2);
    }

    public function getMarginAttribute(): float
    {
        return (float) $this->final_price > 0 ? round(((float) $this->profit / (float) $this->final_price) * 100, 2) : 0;
    }

    public function getIsAvailableAttribute(): bool
    {
        return $this->made_to_order || $this->stock === null || $this->stock > 0;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
