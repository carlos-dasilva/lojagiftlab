<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories', ['categories' => Category::withCount('products')->orderBy('order')->get()]);
    }

    public function store(Request $r)
    {
        $d = $r->validate(['name' => 'required|max:120|unique:categories,name', 'description' => 'nullable|max:1000'], [
            'name.unique' => 'Essa categoria já existe.',
        ]);
        Category::create([...$d, 'slug' => Str::slug($d['name']), 'active' => true]);

        return back()->with('success', 'Categoria criada.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return back()->with('success', 'Categoria excluída.');
    }
}
