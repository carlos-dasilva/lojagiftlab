<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\SalesChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $status = 'published', array $extra = []): Product
    {
        $c = Category::firstOrCreate(['slug' => 'geek'], ['name' => 'Geek', 'active' => true]);

        $product = Product::create(array_merge(['name' => 'Produto Teste', 'slug' => 'produto-teste-'.$status, 'sale_price' => 100, 'cost_price' => 30, 'discount_percentage' => 20, 'status' => $status], $extra));
        $product->categories()->attach($c);

        return $product;
    }

    public function test_public_pages_open(): void
    {
        $this->get('/')->assertOk();
        $this->get('/produtos')->assertOk();
        $this->get('/faq')->assertOk();
    }

    public function test_only_published_products_are_public(): void
    {
        $published = $this->product();
        $draft = $this->product('draft');
        $this->get('/produtos')->assertSee($published->name)->assertDontSee($draft->slug);
        $this->get('/produto/'.$draft->slug)->assertNotFound()->assertSee('Produto não encontrado');
    }

    public function test_missing_product_has_a_branded_not_found_page(): void
    {
        $this->get('/produto/produto-que-nao-existe')
            ->assertNotFound()
            ->assertSee('Produto não encontrado')
            ->assertSee('Explorar produtos')
            ->assertSee(route('catalog'), false);
    }

    public function test_product_description_renders_safe_markdown(): void
    {
        $product = $this->product();
        $product->update([
            'description' => "**Joy-Con não incluso.**\n\n- Consulte as cores disponíveis.\n\n<script>alert('teste')</script>",
        ]);

        $this->get('/produto/'.$product->slug)
            ->assertOk()
            ->assertSee('<strong>Joy-Con não incluso.</strong>', false)
            ->assertSee('<li>Consulte as cores disponíveis.</li>', false)
            ->assertDontSee('<script>', false);
    }

    public function test_marketplace_price_is_rendered_and_cost_is_never_rendered(): void
    {
        $p = $this->product();
        $channel = SalesChannel::create(['name' => 'Loja', 'slug' => 'loja', 'active' => true]);
        $p->salesLinks()->create(['sales_channel_id' => $channel->id, 'url' => 'https://example.com', 'price' => 80, 'active' => true]);
        $this->get('/produto/'.$p->slug)->assertSee('R$ 80,00')->assertDontSee('R$ 30,00');
    }

    public function test_out_of_stock_and_made_to_order_states(): void
    {
        $p = $this->product('published', ['stock' => 0, 'made_to_order' => true]);
        $this->get('/produto/'.$p->slug)->assertSee('Produzido sob encomenda');
    }

    public function test_product_can_be_found_in_more_than_one_category(): void
    {
        $product = $this->product();
        $kitchen = Category::create(['name' => 'Cozinha', 'slug' => 'cozinha', 'active' => true]);
        $product->categories()->attach($kitchen);

        $this->get('/categoria/geek')->assertOk()->assertSee($product->name);
        $this->get('/categoria/cozinha')->assertOk()->assertSee($product->name);
    }

    public function test_customer_can_choose_between_product_sales_channels(): void
    {
        $product = $this->product();
        foreach (['Mercado Livre', 'Shopee', 'OLX'] as $order => $name) {
            $channel = SalesChannel::create(['name' => $name, 'slug' => str($name)->slug(), 'active' => true]);
            $product->salesLinks()->create(['sales_channel_id' => $channel->id, 'url' => 'https://example.com/'.$channel->slug, 'price' => 100 + $order, 'label' => 'Comprar no '.$name, 'order' => $order, 'active' => true]);
        }

        $this->get('/produto/'.$product->slug)
            ->assertOk()
            ->assertSee('Escolha onde comprar')
            ->assertSee('Mercado Livre')->assertSee('R$ 100,00')
            ->assertSee('Shopee')->assertSee('R$ 101,00')
            ->assertSee('OLX')->assertSee('R$ 102,00');
    }

    public function test_favorites_filter_only_returns_selected_products(): void
    {
        $favorite = $this->product('published', ['name' => 'Meu favorito', 'slug' => 'meu-favorito']);
        $other = $this->product('published', ['name' => 'Produto não favorito', 'slug' => 'produto-nao-favorito']);

        $this->get('/produtos?favorites='.$favorite->id)
            ->assertOk()
            ->assertSee('Meu favorito')
            ->assertDontSee($other->name);

        $this->get('/produtos')
            ->assertSee($favorite->name)
            ->assertSee($other->name);
    }

    public function test_product_page_contains_complete_share_information(): void
    {
        $product = $this->product('published', ['name' => 'Presente especial', 'slug' => 'presente-especial', 'short_description' => 'Uma descrição especial para compartilhar.']);
        $product->images()->create(['path' => 'products/capa.jpg', 'alt' => $product->name, 'is_primary' => true, 'order' => 0]);

        $this->get('/produto/'.$product->slug)
            ->assertOk()
            ->assertSee('data-share-title="Presente especial"', false)
            ->assertSee('Uma descrição especial para compartilhar.')
            ->assertSee('products/capa.jpg');
    }
}
