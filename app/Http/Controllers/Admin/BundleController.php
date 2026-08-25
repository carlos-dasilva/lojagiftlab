<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BundleController extends Controller
{
    public function index(Request $request)
    {
        $term = trim((string) $request->query('q'));
        $bundles = Product::where('is_bundle', true)
            ->when($term, fn ($query) => $query->where('name', 'like', "%{$term}%"))
            ->withCount('bundleItems')->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.bundles.index', compact('bundles', 'term'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:160',
            'cost_price' => 'required|numeric|min:0|max:9999999999',
            'status' => 'required|in:draft,published,unavailable,archived',
        ], ['required' => 'O campo :attribute é obrigatório.', 'numeric' => 'Informe um valor válido.', 'min' => 'O valor não pode ser negativo.'], ['name' => 'nome', 'cost_price' => 'custo privado', 'status' => 'status']);

        $baseSlug = Str::slug($data['name']);
        $slug = $baseSlug;
        $suffix = 2;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        $bundle = Product::create($data + ['slug' => $slug, 'condition' => 'new', 'is_bundle' => true]);

        return redirect()->route('admin.bundles.edit', $bundle)->with('success', 'Conjunto criado. Agora adicione os produtos que fazem parte dele.');
    }

    public function edit(Request $request, Product $bundle)
    {
        abort_unless($bundle->is_bundle, 404);
        $term = trim((string) $request->query('q'));
        $selected = $bundle->bundleItems()->orderBy('name')->get();
        $results = collect();

        if ($term !== '') {
            $results = Product::where('is_bundle', false)
                ->whereKeyNot($bundle->id)
                ->where('name', 'like', "%{$term}%")
                ->whereNotIn('id', $selected->pluck('id'))
                ->orderBy('name')->limit(20)->get();
        }

        return view('admin.bundles.edit', compact('bundle', 'selected', 'results', 'term'));
    }

    public function update(Request $request, Product $bundle)
    {
        abort_unless($bundle->is_bundle, 404);
        $data = $request->validate([
            'items' => 'nullable|array',
            'items.*.selected' => 'nullable|boolean',
            'items.*.quantity' => 'nullable|integer|min:1|max:9999',
        ], ['integer' => 'A quantidade deve ser um número inteiro.', 'min' => 'A quantidade mínima é 1.', 'max' => 'A quantidade ultrapassou o limite.']);

        $items = collect($data['items'] ?? [])->filter(fn ($item) => $item['selected'] ?? false);
        $validIds = Product::where('is_bundle', false)->whereIn('id', $items->keys())->pluck('id');
        $sync = $validIds->mapWithKeys(fn ($id) => [(int) $id => ['quantity' => (int) ($items[$id]['quantity'] ?? 1)]])->all();

        DB::transaction(fn () => $bundle->bundleItems()->sync($sync));

        return redirect()->route('admin.bundles.edit', $bundle)->with('success', 'Itens do conjunto atualizados.');
    }
}
