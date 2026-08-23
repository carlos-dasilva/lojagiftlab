<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('admin.dashboard', ['stats' => ['products' => Product::count(), 'published' => Product::where('status', 'published')->count(), 'drafts' => Product::where('status', 'draft')->count(), 'unavailable' => Product::where('status', 'unavailable')->count(), 'made_to_order' => Product::where('made_to_order', true)->count(), 'promotions' => Product::where('discount_percentage', '>', 0)->count(), 'categories' => Category::count()], 'latest' => Product::latest()->take(6)->get()]);
    }
}
