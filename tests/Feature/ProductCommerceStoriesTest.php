<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SalesChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductCommerceStoriesTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $extra = []): Product
    {
        return Product::create(array_merge(['name' => 'Produto completo', 'slug' => 'produto-completo', 'cost_price' => 20, 'condition' => 'new', 'status' => 'published'], $extra));
    }

    public function test_admin_can_add_and_remove_youtube_videos(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = $this->product();
        $data = ['name' => $product->name, 'cost_price' => 20, 'condition' => 'new', 'status' => 'published', 'videos' => ['new_1787174060634' => ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'title' => 'Demonstração']]];
        $this->actingAs($admin)->put(route('admin.products.update', $product), $data)->assertSessionHasNoErrors();
        $this->get(route('products.show', $product))->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false)->assertSee('Abrir Demonstração no YouTube');
        $this->assertDatabaseHas('product_videos', ['product_id' => $product->id, 'order' => 0]);
        $this->actingAs($admin)->put(route('admin.products.update', $product), array_diff_key($data, ['videos' => true]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('product_videos', 0);
    }

    public function test_each_marketplace_has_its_own_price(): void
    {
        $product = $this->product();
        foreach ([['Mercado Livre', 89.90], ['Shopee', 84.50]] as [$name, $price]) {
            $channel = SalesChannel::create(['name' => $name, 'slug' => str($name)->slug(), 'active' => true]);
            $product->salesLinks()->create(['sales_channel_id' => $channel->id, 'url' => 'https://example.com/'.$channel->slug, 'price' => $price, 'active' => true]);
        }
        $this->get(route('products.show', $product))->assertSee('R$ 89,90')->assertSee('R$ 84,50')->assertSee('não incluem frete');
    }

    public function test_customer_can_quote_shipping_by_postal_code(): void
    {
        config(['services.melhor_envio' => ['token' => 'token', 'from_postal_code' => '01001000', 'base_url' => 'https://melhorenvio.test/api/v2', 'user_agent' => 'Gift Lab Test']]);
        Http::fake(['*' => Http::response([
            ['name' => 'PAC', 'price' => '18.75', 'delivery_time' => 5, 'company' => ['name' => 'Correios']],
            ['name' => '.Package', 'price' => '12.00', 'delivery_time' => 4, 'company' => ['name' => 'Jadlog']],
        ])]);
        $product = $this->product(['weight_kg' => 0.5, 'width_cm' => 15, 'height_cm' => 10, 'length_cm' => 20]);
        $this->postJson(route('products.shipping', $product), ['postal_code' => '01310-100'])
            ->assertOk()
            ->assertJsonCount(1, 'quotes')
            ->assertJsonPath('quotes.0.company', 'Correios')
            ->assertJsonPath('quotes.0.price', 18.75)
            ->assertJsonPath('quotes.0.days', 6);
        Http::assertSent(fn ($request) => $request['to']['postal_code'] === '01310100' && $request['products'][0]['weight'] === 0.5);
    }
}
