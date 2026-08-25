<?php

namespace Tests\Feature;

use App\Models\Payable;
use App\Models\Product;
use App\Models\SalesChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceAndBundlesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function product(string $name, array $extra = []): Product
    {
        return Product::create(array_merge([
            'name' => $name,
            'slug' => str($name)->slug(),
            'cost_price' => 20,
            'condition' => 'new',
            'status' => 'published',
        ], $extra));
    }

    public function test_admin_can_register_a_sale_and_see_its_net_value_in_statement(): void
    {
        $product = $this->product('Caneca gamer');
        $channel = SalesChannel::create(['name' => 'Mercado Livre', 'slug' => 'mercado-livre', 'active' => true]);

        $response = $this->actingAs($this->admin())->post(route('admin.finance.sales.store'), [
            'product_id' => $product->id,
            'sales_channel_id' => $channel->id,
            'quantity' => 2,
            'unit_price' => 100,
            'shipping_income' => 10,
            'fee' => 15,
            'sold_at' => now()->toDateString(),
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('sales', ['product_name' => 'Caneca gamer', 'quantity' => 2, 'unit_price' => 100]);
        $this->actingAs($this->admin())->get(route('admin.finance.statement'))
            ->assertOk()
            ->assertSee('2× Caneca gamer')
            ->assertSee('R$ 195,00');
    }

    public function test_payable_only_leaves_the_statement_after_it_is_marked_as_paid(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.finance.payables.store'), [
            'description' => 'Bobina de filamento',
            'category' => 'Matéria-prima',
            'amount' => 75,
            'due_date' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $payable = Payable::firstOrFail();
        $this->actingAs($admin)->get(route('admin.finance.statement'))->assertDontSee('Bobina de filamento');

        $this->actingAs($admin)->patch(route('admin.finance.payables.toggle', $payable))->assertSessionHasNoErrors();
        $this->assertNotNull($payable->fresh()->paid_at);
        $this->actingAs($admin)->get(route('admin.finance.statement'))
            ->assertSee('Bobina de filamento')
            ->assertSee('R$ 75,00');
    }

    public function test_purchase_can_be_split_into_monthly_installments_without_losing_cents(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.finance.payables.store'), [
            'description' => 'Filamentos coloridos',
            'category' => 'Matéria-prima',
            'amount' => 100,
            'installments' => 3,
            'due_date' => '2026-08-31',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success', 'Compra registrada em 3 parcelas.');
        $this->assertDatabaseCount('payables', 3);
        $this->assertSame(100.0, (float) Payable::sum('amount'));
        $this->assertDatabaseHas('payables', ['installment_number' => 1, 'installments_total' => 3, 'amount' => 33.34]);
        $this->assertDatabaseHas('payables', ['installment_number' => 2, 'installments_total' => 3, 'amount' => 33.33]);
        $this->assertDatabaseHas('payables', ['installment_number' => 3, 'installments_total' => 3, 'amount' => 33.33]);
        $this->assertSame(['2026-08-31', '2026-09-30', '2026-10-31'], Payable::orderBy('installment_number')->get()->map(fn (Payable $payable) => $payable->due_date->toDateString())->all());

        $this->actingAs($admin)->get(route('admin.finance.payables'))
            ->assertOk()
            ->assertSee('1/3')
            ->assertSee('2/3')
            ->assertSee('3/3');
    }

    public function test_bundle_can_link_products_with_quantities_and_its_own_marketplace_price(): void
    {
        $base = $this->product('Base para controle');
        $figure = $this->product('Mini figura');

        $admin = $this->admin();
        $response = $this->actingAs($admin)->post(route('admin.bundles.store'), [
            'name' => 'Kit Gamer',
            'cost_price' => 45,
            'status' => 'published',
        ]);

        $response->assertSessionHasNoErrors();
        $bundle = Product::where('slug', 'kit-gamer')->firstOrFail();
        $response->assertRedirect(route('admin.bundles.edit', $bundle));

        $this->actingAs($admin)->put(route('admin.bundles.update', $bundle), [
            'items' => [
                $base->id => ['selected' => 1, 'quantity' => 1],
                $figure->id => ['selected' => 1, 'quantity' => 2],
            ],
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->put(route('admin.products.update', $bundle), [
            'name' => 'Kit Gamer',
            'cost_price' => 45,
            'condition' => 'new',
            'status' => 'published',
            'sales_links' => [[
                'channel' => 'Shopee',
                'url' => 'https://shopee.com.br/kit-gamer',
                'price' => 129.90,
            ]],
        ])->assertSessionHasNoErrors();

        $this->assertTrue($bundle->is_bundle);
        $this->assertDatabaseHas('bundle_product', ['bundle_id' => $bundle->id, 'component_product_id' => $figure->id, 'quantity' => 2]);
        $this->assertDatabaseHas('product_sales_links', ['product_id' => $bundle->id, 'price' => 129.90]);

        $this->get(route('products.show', $bundle))
            ->assertOk()
            ->assertSee('O que vem neste conjunto')
            ->assertSee('Mini figura')
            ->assertSee('2 unidades');
    }

    public function test_bundle_composition_search_only_shows_matching_products(): void
    {
        $admin = $this->admin();
        $bundle = $this->product('Kit cozinha', ['is_bundle' => true]);
        $this->product('Espátula azul');
        $this->product('Controle gamer');

        $this->actingAs($admin)->get(route('admin.bundles.edit', ['bundle' => $bundle, 'q' => 'Espátula']))
            ->assertOk()
            ->assertSee('Espátula azul')
            ->assertDontSee('Controle gamer');
    }

    public function test_finance_area_requires_an_authenticated_admin(): void
    {
        $this->get(route('admin.finance.index'))->assertRedirect(route('admin.login'));
    }
}
