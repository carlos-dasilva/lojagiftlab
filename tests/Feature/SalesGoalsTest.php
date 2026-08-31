<?php

namespace Tests\Feature;

use App\Models\CreditSale;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalesGoal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesGoalsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function sale(string $date, float $unitPrice, int $quantity = 1, float $shipping = 0, float $fee = 0): Sale
    {
        $product = Product::firstOrCreate(['slug' => 'produto-meta'], ['name' => 'Produto Meta', 'cost_price' => 10, 'condition' => 'new', 'status' => 'published']);

        return Sale::create(['product_id' => $product->id, 'product_name' => $product->name, 'quantity' => $quantity, 'unit_price' => $unitPrice, 'shipping_income' => $shipping, 'fee' => $fee, 'sold_at' => $date]);
    }

    public function test_weekly_goal_runs_from_sunday_to_saturday_and_uses_gross_product_sales(): void
    {
        Carbon::setTestNow('2026-08-31 10:00:00');
        $admin = $this->admin();
        $this->sale('2026-08-31', 100, 2, 50, 20);

        $this->actingAs($admin)->post(route('admin.finance.goals.store'), ['period_type' => 'weekly', 'target_amount' => 150])->assertSessionHasNoErrors();

        $goal = SalesGoal::firstOrFail();
        $this->assertSame('weekly', $goal->period_type);
        $this->assertSame('2026-08-30', $goal->effective_from->toDateString());
        $this->assertSame(150.0, (float) $goal->target_amount);
        $this->actingAs($admin)->get(route('admin.finance.goals', ['type' => 'weekly']))
            ->assertOk()
            ->assertSee('30/08/2026 a 05/09/2026')
            ->assertSee('Meta atingida')
            ->assertSee('R$ 200,00')
            ->assertSee('R$ 50,00');
    }

    public function test_changing_a_goal_preserves_the_target_used_by_previous_periods(): void
    {
        $admin = $this->admin();
        Carbon::setTestNow('2026-08-03 10:00:00');
        $this->actingAs($admin)->post(route('admin.finance.goals.store'), ['period_type' => 'weekly', 'target_amount' => 100]);
        $this->sale('2026-08-04', 80);

        Carbon::setTestNow('2026-08-10 10:00:00');
        $this->actingAs($admin)->post(route('admin.finance.goals.store'), ['period_type' => 'weekly', 'target_amount' => 200]);
        $this->sale('2026-08-10', 220);

        $this->assertDatabaseCount('sales_goals', 2);
        $response = $this->actingAs($admin)->get(route('admin.finance.goals', ['type' => 'weekly']));
        $response->assertSee('R$ 200,00')->assertSee('R$ 100,00')->assertSee('R$ 80,00')->assertSee('R$ 20,00');
    }

    public function test_monthly_goal_uses_first_and_last_day_of_month(): void
    {
        Carbon::setTestNow('2026-02-10 10:00:00');
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.finance.goals.store'), ['period_type' => 'monthly', 'target_amount' => 1000]);

        $goal = SalesGoal::firstOrFail();
        $this->assertSame('2026-02-01', $goal->effective_from->toDateString());
        $this->actingAs($admin)->get(route('admin.finance.goals', ['type' => 'monthly']))
            ->assertOk()
            ->assertSee('02/2026')
            ->assertSee('Em andamento');
    }

    public function test_credit_sale_counts_toward_sales_goal_even_before_payment(): void
    {
        Carbon::setTestNow('2026-08-31 10:00:00');
        $admin = $this->admin();
        $product = Product::create(['name' => 'Produto fiado', 'slug' => 'produto-fiado', 'cost_price' => 10, 'condition' => 'new', 'status' => 'published']);
        CreditSale::create(['product_id' => $product->id, 'product_name' => $product->name, 'customer_name' => 'Cliente', 'quantity' => 2, 'unit_price' => 60, 'sold_at' => now(), 'received_at' => null]);
        $this->actingAs($admin)->post(route('admin.finance.goals.store'), ['period_type' => 'weekly', 'target_amount' => 100]);

        $this->actingAs($admin)->get(route('admin.finance.goals', ['type' => 'weekly']))
            ->assertOk()
            ->assertSee('Meta atingida')
            ->assertSee('R$ 120,00');
    }
}
