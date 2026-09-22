<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $r)
    {
        $r->validate(['q' => 'nullable|string|max:200', 'category' => 'nullable|string|max:160', 'favorites' => 'nullable|string|max:2000', 'sort' => 'nullable|string', 'min_price' => 'nullable|numeric|min:0', 'max_price' => 'nullable|numeric|min:0', 'condition' => 'nullable|in:new,used,like_new,custom']);
        $favoriteIds = collect(explode(',', (string) $r->favorites))->filter(fn ($id) => ctype_digit($id))->map(fn ($id) => (int) $id)->unique()->take(100);
        $favoritesMode = $r->has('favorites');
        $q = Product::published()->with(['categories', 'primaryImage', 'salesLinks'])->withMin('salesLinks', 'price')->when($favoritesMode, fn ($query) => $query->whereIn('id', $favoriteIds))->search($r->q)->when($r->category, fn ($q, $v) => $q->whereHas('categories', fn ($q) => $q->where('active', true)->where('slug', $v)))->when($r->available, fn ($q) => $q->where(fn ($q) => $q->whereNull('stock')->orWhere('stock', '>', 0)->orWhere('made_to_order', true)));
        match ($r->sort) {
            'price_asc' => $q->orderBy('sales_links_min_price'),'price_desc' => $q->orderByDesc('sales_links_min_price'),'views' => $q->withCount('views')->orderByDesc('views_count'),default => $q->latest()
        };
        $q->with(['categories' => fn ($query) => $query->where('active', true)])
            ->when($r->boolean('promotion'), fn ($query) => $query->promoted())
            ->when($r->filled('condition'), fn ($query) => $query->where('condition', $r->condition))
            ->when($r->filled('min_price') || $r->filled('max_price'), fn ($query) => $query->whereHas('salesLinks', fn ($links) => $links
                ->when($r->filled('min_price'), fn ($links) => $links->where('price', '>=', $r->min_price))
                ->when($r->filled('max_price'), fn ($links) => $links->where('price', '<=', $r->max_price))));

        return view('catalog.index', ['products' => $q->paginate(12)->withQueryString(), 'categories' => Category::where('active', true)->orderBy('name')->get(), 'favoritesMode' => $favoritesMode, 'currentCategory' => $r->category ? Category::where('active', true)->where('slug', $r->category)->first() : null]);
    }

    public function category(Category $category, Request $r)
    {
        abort_unless($category->active, 404);
        $r->merge(['category' => $category->slug]);

        return $this->index($r);
    }

    public function show(Product $product, Request $r)
    {
        if ($product->status->value !== 'published') {
            return response()->view('errors.product-not-found', [], 404);
        }
        $product->load(['categories' => fn ($query) => $query->where('active', true), 'images', 'primaryImage', 'videos', 'attributes', 'variants' => fn ($query) => $query->where('active', true), 'tags', 'salesLinks.channel', 'bundleItems' => fn ($query) => $query->published()->with('primaryImage')]);
        $hash = hash('sha256', $r->session()->getId());
        $product->views()->firstOrCreate(['session_hash' => $hash], ['viewed_at' => now()]);
        $categoryIds = $product->categories->pluck('id');
        $related = Product::published()->where('id', '!=', $product->id)
            ->when($categoryIds->isNotEmpty(), fn ($query) => $query->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $categoryIds)))
            ->with(['categories', 'primaryImage', 'salesLinks'])->take(4)->get();

        return view('catalog.show', compact('product', 'related'));
    }
}
