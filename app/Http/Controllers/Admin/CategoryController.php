<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function index()
    {
        return $this->form(new Category(['active' => true, 'order' => 0]));
    }

    private function form(Category $editing)
    {
        return view('admin.categories', ['categories' => Category::with('parent')->withCount('products')->orderBy('order')->get(), 'editing' => $editing]);
    }

    public function edit(Category $category)
    {
        return $this->form($category);
    }

    public function update(Request $r, Category $category)
    {
        return $this->save($r, $category);
    }

    public function store(Request $r)
    {
        return $this->save($r, new Category);
    }

    private function save(Request $r, Category $category)
    {
        $r->merge(['slug' => Str::slug($r->input('slug') ?: $r->input('name', ''))]);
        $d = $r->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('categories')->ignore($category->id)],
            'slug' => ['required', 'string', 'max:160', Rule::unique('categories')->ignore($category->id)],
            'description' => 'nullable|string|max:1000', 'parent_id' => 'nullable|integer|exists:categories,id',
            'order' => 'nullable|integer|min:0', 'active' => 'nullable|boolean',
            'icon' => 'nullable|string|max:10', 'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], ['unique' => 'Já existe uma categoria com esse nome ou endereço.']);
        $parent = isset($d['parent_id']) ? Category::find($d['parent_id']) : null;
        $seen = [];
        while ($parent) {
            if ($parent->id === $category->id || isset($seen[$parent->id])) {
                throw ValidationException::withMessages(['parent_id' => 'A hierarquia não pode formar um ciclo.']);
            }
            $seen[$parent->id] = true;
            $parent = $parent->parent;
        }
        $d['active'] = $r->boolean('active', ! $category->exists);
        $d['order'] = $d['order'] ?? 0;
        unset($d['image']);
        $newPath = $r->hasFile('image') ? app(ImageService::class)->single($r->file('image'), 'categories') : null;
        $oldPath = $category->image;
        if ($newPath) {
            $d['image'] = $newPath;
        }
        try {
            $category->fill($d)->save();
        } catch (\Throwable $error) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            } throw $error;
        }
        if ($newPath && $oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()->route('admin.categories.index')->with('success', 'Categoria salva.');
    }

    public function destroy(Category $category)
    {
        $path = $category->image;
        $category->delete();
        if ($path) {
            Storage::disk('public')->delete($path);
        }

        return redirect()->route('admin.categories.index')->with('success', 'Categoria excluída.');
    }
}
