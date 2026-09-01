<?php

namespace Tests\Feature;

use App\Models\CreditSale;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditSalesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function product(): Product
    {
        return Product::create(['name' => 'Luminária', 'slug' => 'luminaria', 'cost_price' => 20, 'condition' => 'new', 'status' => 'published']);
    }

    public function test_credit_sale_stays_out_of_statement_until_payment_is_received(): void
    {
        $admin = $this->admin();
        $product = $this->product();

        $response = $this->actingAs($admin)->post(route('admin.finance.credits.store'), [
            'customer_name' => 'Maria Silva',
            'customer_contact' => 'WhatsApp 99999-9999',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 50]],
            'shipping_income' => 10,
            'fee' => 5,
            'sold_at' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
        ]);

        $response->assertSessionHasNoErrors();
        $credit = CreditSale::firstOrFail();
        $this->assertFalse($credit->is_received);
        $this->assertFalse($credit->is_delivered);
        $this->assertSame(105.0, $credit->net_total);
        $this->actingAs($admin)->get(route('admin.finance.statement'))->assertDontSee('Fiado recebido de Maria Silva');

        $this->actingAs($admin)->patch(route('admin.finance.credits.received', $credit), ['received_on' => now()->toDateString()])->assertSessionHasNoErrors();
        $this->assertTrue($credit->fresh()->is_received);
        $this->actingAs($admin)->get(route('admin.finance.statement'))
            ->assertSee('Fiado recebido de Maria Silva')
            ->assertSee('R$ 105,00');
    }

    public function test_delivery_status_can_change_without_marking_payment_as_received(): void
    {
        $admin = $this->admin();
        $product = $this->product();
        $credit = CreditSale::create(['product_id' => $product->id, 'product_name' => $product->name, 'customer_name' => 'João', 'quantity' => 1, 'unit_price' => 70, 'sold_at' => now()]);

        $this->actingAs($admin)->patch(route('admin.finance.credits.delivered', $credit))->assertSessionHasNoErrors();

        $credit->refresh();
        $this->assertTrue($credit->is_delivered);
        $this->assertFalse($credit->is_received);
        $this->actingAs($admin)->get(route('admin.finance.credits'))->assertSee('✓ Entregue')->assertSee('Aguardando pagamento');
    }

    public function test_credit_sale_requires_customer_product_quantity_price_and_sale_date(): void
    {
        $this->actingAs($this->admin())->post(route('admin.finance.credits.store'), [])
            ->assertSessionHasErrors(['customer_name', 'items', 'sold_at']);
    }

    public function test_credit_sale_can_have_catalog_and_generic_items_and_be_edited(): void
    {
        $admin = $this->admin();
        $product = $this->product();

        $this->actingAs($admin)->post(route('admin.finance.credits.store'), [
            'customer_name' => 'Carlos',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 70],
                ['product_id' => null, 'item_name' => 'Embalagem especial', 'quantity' => 2, 'unit_price' => 5],
            ],
            'sold_at' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $credit = CreditSale::with('items')->firstOrFail();
        $this->assertCount(2, $credit->items);
        $this->assertSame(80.0, $credit->gross_total);
        $this->assertDatabaseHas('credit_sale_items', ['credit_sale_id' => $credit->id, 'product_id' => null, 'item_name' => 'Embalagem especial']);
        $this->assertDatabaseMissing('products', ['name' => 'Embalagem especial']);
        $this->actingAs($admin)->get(route('admin.finance.credits.edit', $credit))->assertOk()->assertSee('Editar fiado')->assertSee('Embalagem especial');

        $this->actingAs($admin)->put(route('admin.finance.credits.update', $credit), [
            'customer_name' => 'Carlos corrigido',
            'items' => [['product_id' => null, 'item_name' => 'Serviço avulso', 'quantity' => 1, 'unit_price' => 30]],
            'sold_at' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $credit->refresh()->load('items');
        $this->assertSame('Carlos corrigido', $credit->customer_name);
        $this->assertCount(1, $credit->items);
        $this->assertSame('Serviço avulso', $credit->items->first()->item_name);
    }
}
