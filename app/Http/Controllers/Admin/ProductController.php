<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVideo;
use App\Models\SalesChannel;
use App\Models\Tag;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    private array $uploadedPaths = [];

    private function transaction(callable $callback)
    {
        $this->uploadedPaths = [];
        try {
            return DB::transaction($callback);
        } catch (\Throwable $error) {
            Storage::disk('public')->delete($this->uploadedPaths);
            throw $error;
        }
    }

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
        $product = $this->transaction(function () use ($r) {
            $product = Product::create($this->data($r));
            $product->categories()->sync($r->validated('categories', []));
            $this->salesLinks($r, $product);
            $this->videos($r, $product);
            $this->images($r, $product);
            $this->details($r, $product);

            return $product;
        });

        return redirect()->route('admin.products.index')->with('success', 'Produto criado com sucesso.');
    }

    public function edit(Product $product)
    {
        return view('admin.products.form', ['product' => $product->load(['categories', 'images', 'videos', 'allSalesLinks.channel', 'attributes', 'variants', 'tags']), 'categories' => Category::orderBy('name')->get(), 'salesChannels' => SalesChannel::orderBy('name')->get()]);
    }

    public function update(ProductRequest $r, Product $product)
    {
        $this->transaction(function () use ($r, $product) {
            $oldSlug = $product->slug;
            $product->update($this->data($r) + ['is_bundle' => $product->is_bundle]);
            if ($oldSlug !== $product->slug) {
                DB::table('product_redirects')->updateOrInsert(['slug' => $oldSlug], ['product_id' => $product->id]);
                DB::table('product_redirects')->where('slug', $product->slug)->delete();
            }
            $product->categories()->sync($r->validated('categories', []));
            $this->salesLinks($r, $product);
            $this->videos($r, $product);
            $this->images($r, $product);
            $this->details($r, $product);
        });

        return redirect()->route('admin.products.edit', $product)->with('success', 'Produto atualizado com sucesso.');
    }

    public function destroy(Product $product)
    {
        $paths = $product->images->flatMap(fn ($image) => $image->files())->all();
        $product->delete();
        Storage::disk('public')->delete($paths);

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

        $paths = $image->files();
        DB::transaction(function () use ($product, $image) {
            $wasPrimary = $image->is_primary;
            $image->delete();

            if ($wasPrimary) {
                $product->images()->first()?->update(['is_primary' => true]);
            }
        });
        Storage::disk('public')->delete($paths);

        return response()->json(['message' => 'Imagem excluída com sucesso.']);
    }

    private function data(ProductRequest $r): array
    {
        $d = $r->validated();
        $d['slug'] = $d['slug'] ?: Str::slug($d['name']);
        foreach (['featured', 'is_new', 'customizable', 'made_to_order'] as $key) {
            $d[$key] = $r->boolean($key);
        }unset($d['images'], $d['categories'], $d['sales_links'], $d['videos'], $d['bundle_items'], $d['is_bundle'], $d['attributes'], $d['variants'], $d['tags_text'], $d['image_order']);
        $d['sale_price'] = null;
        $d['discount_percentage'] = 0;

        return $d;
    }

    private function images(ProductRequest $r, Product $p): void
    {
        foreach ($r->file('images', []) as $i => $file) {
            $paths = app(ImageService::class)->store($file);
            $this->uploadedPaths = array_merge($this->uploadedPaths, array_values($paths));
            $p->images()->create($paths + ['alt' => $p->name, 'is_primary' => ! $p->images()->exists() && $i === 0, 'order' => $p->images()->count()]);
        }
    }

    private function salesLinks(ProductRequest $request, Product $product): void
    {
        $product->allSalesLinks()->delete();

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
                'original_price' => $link['original_price'] ?? null,
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

    private function details(ProductRequest $request, Product $product): void
    {
        // Preserve existing data for clients that do not submit the new sections.
        if ($request->has('details_present')) {
            $product->attributes()->delete();
            foreach (array_values($request->validated('attributes', [])) as $order => $attribute) {
                $product->attributes()->create($attribute + ['order' => $order]);
            }
            $product->variants()->delete();
            foreach ($request->validated('variants', []) as $variant) {
                $product->variants()->create($variant + ['active' => false]);
            }
            $tags = collect(explode(',', $request->validated('tags_text', '') ?? ''))->map(fn ($name) => trim($name))->filter()->take(30)->map(fn ($name) => Tag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])->id);
            $product->tags()->sync($tags);
        }
        foreach ($request->validated('image_order', []) as $id => $order) {
            $product->images()->whereKey($id)->update(['order' => $order]);
        }
    }
}
