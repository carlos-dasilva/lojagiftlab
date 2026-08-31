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
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 50,
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

        $this->actingAs($admin)->patch(route('admin.finance.credits.received', $credit))->assertSessionHasNoErrors();
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
            ->assertSessionHasErrors(['customer_name', 'product_id', 'quantity', 'unit_price', 'sold_at']);
    }
}
