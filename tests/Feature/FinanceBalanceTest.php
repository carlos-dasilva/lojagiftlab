<?php

namespace Tests\Feature;

use App\Models\CreditSale;
use App\Models\Payable;
use App\Models\Sale;
use App\Models\User;
use App\Services\FinanceBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_balance_includes_all_months_and_only_received_or_paid_records(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 22));
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Sale::create(['product_name' => 'Venda antiga', 'quantity' => 3, 'unit_price' => '10.10', 'shipping_income' => '2.50', 'fee' => '1.20', 'sold_at' => '2026-01-01']);
        Sale::create(['product_name' => 'Venda atual', 'quantity' => 1, 'unit_price' => '5.01', 'sold_at' => '2026-09-01']);
        $credit = CreditSale::create(['product_name' => 'Legado', 'customer_name' => 'Cliente', 'quantity' => 1, 'unit_price' => 999, 'shipping_income' => '1.10', 'fee' => '0.20', 'sold_at' => '2026-02-01', 'received_at' => '2026-03-01']);
        $credit->items()->createMany([['item_name' => 'A', 'quantity' => 3, 'unit_price' => '0.10'], ['item_name' => 'B', 'quantity' => 2, 'unit_price' => '2.35']]);
        CreditSale::create(['product_name' => 'Pendente', 'customer_name' => 'Outro', 'quantity' => 1, 'unit_price' => 500, 'sold_at' => '2026-01-01']);
        Payable::create(['description' => 'Pago antigo', 'amount' => '20.11', 'due_date' => '2026-02-01', 'paid_at' => '2026-02-02']);
        Payable::create(['description' => 'Vencida', 'amount' => '7.77', 'due_date' => '2026-01-01']);
        Payable::create(['description' => 'Futura', 'amount' => '8.88', 'due_date' => '2027-01-01']);
        $this->assertSame(['balance_cents' => 2240, 'pending_cents' => 1665], app(FinanceBalanceService::class)->totals());
        $this->get(route('admin.finance.index'))->assertOk()->assertViewHas('balance', 22.4)->assertViewHas('pending', 16.65)->assertViewHas('income', 5.01)->assertSee('Saldo atual da loja')->assertSee('Total a pagar')->assertSee('R$ 22,40');
    }

    public function test_balance_recalculates_after_receipt_payment_edit_reopening_and_deletion(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $credit = CreditSale::create(['customer_name' => 'Cliente', 'product_name' => 'Peça', 'quantity' => 1, 'unit_price' => '50.25', 'sold_at' => today()]);
        $bill = Payable::create(['description' => 'Conta', 'amount' => '70.50', 'due_date' => today()]);
        $this->get(route('admin.finance.index'))->assertViewHas('balance', 0)->assertViewHas('pending', 70.5);
        $this->patch(route('admin.finance.credits.received', $credit), ['received_on' => today()->toDateString()])->assertSessionHasNoErrors();
        $this->get(route('admin.finance.index'))->assertViewHas('balance', 50.25);
        $this->patch(route('admin.finance.payables.toggle', $bill))->assertSessionHasNoErrors();
        $this->get(route('admin.finance.index'))->assertViewHas('balance', -20.25)->assertViewHas('pending', 0)->assertSee('R$ -20,25');
        $this->put(route('admin.finance.credits.update', $credit), ['customer_name' => 'Cliente', 'sold_at' => today()->toDateString(), 'items' => [['item_name' => 'Corrigido', 'quantity' => 2, 'unit_price' => '30.10']]])->assertSessionHasNoErrors();
        $this->get(route('admin.finance.index'))->assertViewHas('balance', -10.3);
        $this->patch(route('admin.finance.credits.received', $credit))->assertSessionHasNoErrors();
        $this->get(route('admin.finance.index'))->assertViewHas('balance', -70.5);
        $this->patch(route('admin.finance.payables.toggle', $bill))->assertSessionHasNoErrors();
        $this->get(route('admin.finance.index'))->assertViewHas('balance', 0)->assertViewHas('pending', 70.5);
        $this->delete(route('admin.finance.payables.destroy', $bill))->assertSessionHasNoErrors();
        $this->get(route('admin.finance.index'))->assertViewHas('pending', 0);
        $sale = Sale::create(['product_name' => 'Venda', 'quantity' => 1, 'unit_price' => '12.34', 'sold_at' => today()]);
        $this->get(route('admin.finance.index'))->assertViewHas('balance', 12.34);
        $this->delete(route('admin.finance.sales.destroy', $sale))->assertSessionHasNoErrors();
        $this->get(route('admin.finance.index'))->assertViewHas('balance', 0);
    }

    public function test_received_credit_cannot_be_edited_to_a_sale_date_after_receipt(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $credit = CreditSale::create(['customer_name' => 'Cliente', 'product_name' => 'Peça', 'quantity' => 1, 'unit_price' => 10, 'sold_at' => '2026-01-01', 'received_at' => '2026-01-02']);
        $this->put(route('admin.finance.credits.update', $credit), ['customer_name' => 'Cliente', 'sold_at' => '2026-01-03', 'items' => [['item_name' => 'Peça', 'quantity' => 1, 'unit_price' => 10]]])->assertSessionHasErrors('sold_at');
        $this->assertSame('2026-01-01', $credit->fresh()->sold_at->toDateString());
    }
}
