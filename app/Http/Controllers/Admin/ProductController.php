<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVideo;
use App\Models\SalesChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $term = trim((string) $request->query('q'));
        $products = Product::with(['categories', 'salesLinks', 'primaryImage'])
            ->when($term, fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$term}%")
                ->orWhere('slug', 'like', "%{$term}%")
                ->orWhere('short_description', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhereHas('categories', fn ($query) => $query->where('name', 'like', "%{$term}%"))))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.products.index', compact('products', 'term'));
    }

    public function create()
    {
        return view('admin.products.form', ['product' => new Product, 'categories' => Category::orderBy('name')->get(), 'salesChannels' => SalesChannel::orderBy('name')->get()]);
    }

    public function store(ProductRequest $r)
    {
        $product = DB::transaction(function () use ($r) {
            $product = Product::create($this->data($r));
            $product->categories()->sync($r->validated('categories', []));
            $this->salesLinks($r, $product);
            $this->videos($r, $product);
            $this->images($r, $product);

            return $product;
        });

        return redirect()->route('admin.products.index')->with('success', 'Produto criado com sucesso.');
    }

    public function edit(Product $product)
    {
        return view('admin.products.form', ['product' => $product->load(['categories', 'images', 'videos', 'salesLinks.channel']), 'categories' => Category::orderBy('name')->get(), 'salesChannels' => SalesChannel::orderBy('name')->get()]);
    }

    public function update(ProductRequest $r, Product $product)
    {
        DB::transaction(function () use ($r, $product) {
            $product->update($this->data($r));
            $product->categories()->sync($r->validated('categories', []));
            $this->salesLinks($r, $product);
            $this->videos($r, $product);
            $this->images($r, $product);
        });

        return redirect()->route('admin.products.edit', $product)->with('success', 'Produto atualizado com sucesso.');
    }

    public function destroy(Product $product)
    {
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->path);
        }$product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Produto excluído.');
    }

    public function primaryImage(Product $product, ProductImage $image)
    {
        abort_unless($image->product_id === $product->id, 404);

        DB::transaction(function () use ($product, $image) {
            $product->images()->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
        });

        return response()->json(['message' => 'Imagem definida como capa.']);
    }

    public function destroyImage(Product $product, ProductImage $image)
    {
        abort_unless($image->product_id === $product->id, 404);

        DB::transaction(function () use ($product, $image) {
            $wasPrimary = $image->is_primary;
            Storage::disk('public')->delete($image->path);
            $image->delete();

            if ($wasPrimary) {
                $product->images()->first()?->update(['is_primary' => true]);
            }
        });

        return response()->json(['message' => 'Imagem excluída com sucesso.']);
    }

    private function data(ProductRequest $r): array
    {
        $d = $r->validated();
        $d['slug'] = $d['slug'] ?: Str::slug($d['name']);
        foreach (['featured', 'is_new', 'customizable', 'made_to_order'] as $key) {
            $d[$key] = $r->boolean($key);
        }unset($d['images'], $d['categories'], $d['sales_links'], $d['videos']);
        $d['sale_price'] = null;
        $d['discount_percentage'] = 0;

        return $d;
    }

    private function images(ProductRequest $r, Product $p): void
    {
        foreach ($r->file('images', []) as $i => $file) {
            $p->images()->create(['path' => $file->store('products', 'public'), 'alt' => $p->name, 'is_primary' => ! $p->images()->exists() && $i === 0, 'order' => $p->images()->count() + $i]);
        }
    }

    private function salesLinks(ProductRequest $request, Product $product): void
    {
        $product->salesLinks()->delete();

        $order = 0;

        foreach ($request->validated('sales_links', []) as $link) {
            if (blank($link['channel'] ?? null) || blank($link['url'] ?? null)) {
                continue;
            }

            $slug = Str::slug($link['channel']);
            $channel = SalesChannel::firstOrCreate(['slug' => $slug], [
                'name' => trim($link['channel']),
                'color' => match ($slug) {
                    'mercado-livre' => '#3483FA',
                    'shopee' => '#EE4D2D',
                    'olx' => '#6E0AD6',
                    default => '#7139C6',
                },
                'active' => true,
            ]);

            $product->salesLinks()->create([
                'sales_channel_id' => $channel->id,
                'url' => $link['url'],
                'price' => $link['price'],
                'label' => 'Comprar no '.$channel->name,
                'order' => $order++,
                'active' => true,
            ]);
        }
    }

    private function videos(ProductRequest $request, Product $product): void
    {
        $product->videos()->delete();
        $order = 0;
        foreach ($request->validated('videos', []) as $video) {
            $youtubeId = ProductVideo::idFromUrl($video['url'] ?? null);
            if ($youtubeId) {
                $product->videos()->create(['youtube_id' => $youtubeId, 'title' => $video['title'] ?? null, 'order' => $order++]);
            }
        }
    }
}
