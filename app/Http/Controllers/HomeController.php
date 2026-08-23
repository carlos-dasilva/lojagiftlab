<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('home', ['categories' => Category::where('active', true)->whereNull('parent_id')->withCount(['products' => fn ($query) => $query->published()])->orderBy('order')->take(8)->get(), 'featured' => Product::published()->with(['categories', 'primaryImage'])->where('featured', true)->take(8)->get(), 'newProducts' => Product::published()->with(['categories', 'primaryImage'])->where('is_new', true)->latest()->take(4)->get(), 'banners' => Banner::where('active', true)->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))->orderBy('order')->get()]);
    }
}
