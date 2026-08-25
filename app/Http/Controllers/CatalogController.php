<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $r)
    {
        $favoriteIds = collect(explode(',', (string) $r->favorites))->filter(fn ($id) => ctype_digit($id))->map(fn ($id) => (int) $id)->unique()->take(100);
        $favoritesMode = $r->has('favorites');
        $q = Product::published()->with(['categories', 'primaryImage', 'salesLinks'])->withMin('salesLinks', 'price')->when($favoritesMode, fn ($query) => $query->whereIn('id', $favoriteIds))->search($r->q)->when($r->category, fn ($q, $v) => $q->whereHas('categories', fn ($q) => $q->where('slug', $v)))->when($r->available, fn ($q) => $q->where(fn ($q) => $q->whereNull('stock')->orWhere('stock', '>', 0)->orWhere('made_to_order', true)));
        match ($r->sort) {
            'price_asc' => $q->orderBy('sales_links_min_price'),'price_desc' => $q->orderByDesc('sales_links_min_price'),'views' => $q->withCount('views')->orderByDesc('views_count'),default => $q->latest()
        };

        return view('catalog.index', ['products' => $q->paginate(12)->withQueryString(), 'categories' => Category::where('active', true)->orderBy('name')->get(), 'favoritesMode' => $favoritesMode]);
    }

    public function category(Category $category, Request $r)
    {
        $r->merge(['category' => $category->slug]);

        return $this->index($r);
    }

    public function show(Product $product, Request $r)
    {
        if ($product->status->value !== 'published') {
            return response()->view('errors.product-not-found', [], 404);
        }
        $product->load(['categories', 'images', 'videos', 'attributes', 'tags', 'salesLinks.channel', 'bundleItems.primaryImage']);
        $hash = hash('sha256', $r->session()->getId());
        $product->views()->firstOrCreate(['session_hash' => $hash], ['viewed_at' => now()]);
        $categoryIds = $product->categories->pluck('id');
        $related = Product::published()->where('id', '!=', $product->id)
            ->when($categoryIds->isNotEmpty(), fn ($query) => $query->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $categoryIds)))
            ->with(['categories', 'primaryImage', 'salesLinks'])->take(4)->get();

        return view('catalog.show', compact('product', 'related'));
    }
}
