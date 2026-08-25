<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_is_automatically_normalized_when_creating_product(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post('/admin/products', [
            'name' => 'Caneca Mágica São Paulo',
            'slug' => 'Caneca Mágica São Paulo',
            'cost_price' => 20,
            'sale_price' => 50,
            'discount_percentage' => 0,
            'condition' => 'new',
            'status' => 'draft',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', ['slug' => 'caneca-magica-sao-paulo']);
    }

    public function test_product_validation_message_is_presented_in_portuguese(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post('/admin/products', []);

        $response->assertSessionHasErrors(['name']);
        $this->assertSame('O campo nome é obrigatório.', session('errors')->first('name'));
    }

    public function test_product_accepts_multiple_categories(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $categories = collect(['Geek', 'Cozinha', 'Gamer'])->map(fn ($name) => Category::create([
            'name' => $name,
            'slug' => str($name)->slug(),
            'active' => true,
        ]));

        $response = $this->actingAs($admin)->post('/admin/products', [
            'name' => 'Caneca gamer de cozinha',
            'cost_price' => 20,
            'sale_price' => 50,
            'discount_percentage' => 0,
            'condition' => 'new',
            'status' => 'draft',
            'categories' => $categories->pluck('id')->all(),
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('category_product', 3);
    }

    public function test_changing_slug_redirects_to_the_new_edit_url(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = Product::create([
            'name' => 'Hollow Knight',
            'slug' => 'hollow',
            'cost_price' => 20,
            'sale_price' => 50,
            'discount_percentage' => 0,
            'condition' => 'new',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin)->put('/admin/products/hollow', [
            'name' => 'Hollow Knight',
            'slug' => 'hollow-tes',
            'cost_price' => 20,
            'sale_price' => 50,
            'discount_percentage' => 0,
            'condition' => 'new',
            'status' => 'draft',
        ]);

        $response->assertRedirect('/admin/products/hollow-tes/edit');
        $this->get('/admin/products/hollow-tes/edit')->assertOk();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'slug' => 'hollow-tes']);
    }

    public function test_product_accepts_multiple_dynamic_sales_links(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post('/admin/products', [
            'name' => 'Produto em vários canais',
            'cost_price' => 20,
            'sale_price' => 50,
            'discount_percentage' => 0,
            'condition' => 'new',
            'status' => 'published',
            'sales_links' => [
                ['channel' => 'Mercado Livre', 'url' => 'https://mercadolivre.com.br/anuncio', 'price' => 50],
                ['channel' => 'Shopee', 'url' => 'https://shopee.com.br/anuncio', 'price' => 48],
                ['channel' => 'OLX', 'url' => 'https://olx.com.br/anuncio', 'price' => 45],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('product_sales_links', 3);
        $this->assertDatabaseHas('sales_channels', ['name' => 'OLX', 'slug' => 'olx']);
    }

    public function test_sales_link_must_be_a_complete_web_address(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post('/admin/products', [
            'name' => 'Produto com link inválido',
            'cost_price' => 20,
            'sale_price' => 50,
            'discount_percentage' => 0,
            'condition' => 'new',
            'status' => 'draft',
            'sales_links' => [['channel' => 'OLX', 'url' => 'olx.com.br/anuncio']],
        ]);

        $response->assertSessionHasErrors(['sales_links.0.url']);
    }

    public function test_instagram_direct_channel_always_uses_store_profile_url(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post('/admin/products', [
            'name' => 'Produto vendido no Instagram',
            'cost_price' => 20,
            'condition' => 'new',
            'status' => 'published',
            'sales_links' => [[
                'channel' => 'Direct do Instagram',
                'url' => 'https://exemplo.com/link-incorreto',
                'price' => 50,
            ]],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_sales_links', ['url' => 'https://www.instagram.com/lojagiftlab/', 'price' => 50]);
        $this->assertDatabaseHas('sales_channels', ['name' => 'Direct do Instagram', 'slug' => 'direct-do-instagram']);
    }

    public function test_product_edit_form_opens_without_sales_links(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = Product::create([
            'name' => 'Produto sem links',
            'slug' => 'produto-sem-links',
            'cost_price' => 20,
            'sale_price' => 50,
            'discount_percentage' => 0,
            'condition' => 'new',
            'status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->get('/admin/products/'.$product->slug.'/edit')
            ->assertOk()
            ->assertSee('Links de compra');
    }

    public function test_admin_product_address_without_edit_redirects_to_edit_form(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = Product::create(['name' => 'Produto protegido', 'slug' => 'produto-protegido', 'cost_price' => 20, 'condition' => 'new', 'status' => 'draft']);

        $this->actingAs($admin)->get('/admin/products/create')
            ->assertOk()
            ->assertSee('Novo produto');

        $this
            ->get('/admin/products/'.$product->slug)
            ->assertRedirect('/admin/products/'.$product->slug.'/edit');
    }

    public function test_product_list_uses_name_for_editing_and_delete_stays_inside_form(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = Product::create(['name' => 'Produto organizado', 'slug' => 'produto-organizado', 'cost_price' => 20, 'condition' => 'new', 'status' => 'draft']);

        $this->actingAs($admin)->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee(route('admin.products.edit', $product), false)
            ->assertDontSee('Excluir produto');

        $this->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Excluir produto')
            ->assertSee(route('admin.products.destroy', $product), false);
    }

    public function test_admin_can_search_products_by_name_and_category(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $geek = Category::create(['name' => 'Geek', 'slug' => 'geek', 'active' => true]);
        $found = Product::create(['name' => 'Hollow Knight', 'slug' => 'hollow-knight', 'cost_price' => 20, 'condition' => 'new', 'status' => 'published']);
        $found->categories()->attach($geek);
        Product::create(['name' => 'Vaso tradicional', 'slug' => 'vaso-tradicional', 'cost_price' => 20, 'condition' => 'new', 'status' => 'published']);

        $this->actingAs($admin)->get(route('admin.products.index', ['q' => 'Hollow']))
            ->assertOk()->assertSee('Hollow Knight')->assertDontSee('Vaso tradicional');

        $this->get(route('admin.products.index', ['q' => 'Geek']))
            ->assertOk()->assertSee('Hollow Knight')->assertDontSee('Vaso tradicional');
    }

    public function test_dynamic_sales_link_identifier_is_not_used_as_database_order(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = Product::create([
            'name' => 'Produto com link dinâmico',
            'slug' => 'produto-com-link-dinamico',
            'cost_price' => 20,
            'sale_price' => 50,
            'discount_percentage' => 0,
            'condition' => 'new',
            'status' => 'published',
        ]);

        $response = $this->actingAs($admin)->put('/admin/products/'.$product->slug, [
            'name' => $product->name,
            'slug' => $product->slug,
            'cost_price' => 20,
            'sale_price' => 50,
            'discount_percentage' => 0,
            'condition' => 'new',
            'status' => 'published',
            'sales_links' => [
                'new_1786993956648' => [
                    'channel' => 'Mercado Livre',
                    'url' => 'https://mercadolivre.com.br/anuncio',
                    'price' => 50,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_sales_links', [
            'product_id' => $product->id,
            'order' => 0,
        ]);
    }

    public function test_admin_can_choose_cover_and_delete_product_images(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);
        $product = Product::create(['name' => 'Produto com galeria', 'slug' => 'produto-com-galeria', 'cost_price' => 20, 'sale_price' => 50, 'discount_percentage' => 0, 'condition' => 'new', 'status' => 'draft']);
        $first = $product->images()->create(['path' => 'products/primeira.jpg', 'is_primary' => true, 'order' => 0]);
        $second = $product->images()->create(['path' => 'products/segunda.jpg', 'is_primary' => false, 'order' => 1]);

        $this->actingAs($admin)->patchJson(route('admin.products.images.primary', [$product, $second]))->assertOk();
        $this->assertDatabaseHas('product_images', ['id' => $second->id, 'is_primary' => true]);
        $this->assertDatabaseHas('product_images', ['id' => $first->id, 'is_primary' => false]);

        $this->deleteJson(route('admin.products.images.destroy', [$product, $second]))->assertOk();
        $this->assertDatabaseMissing('product_images', ['id' => $second->id]);
        $this->assertDatabaseHas('product_images', ['id' => $first->id, 'is_primary' => true]);
    }
}
