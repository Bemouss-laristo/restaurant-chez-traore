<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Budget;
use App\Models\CashSession;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Staff;
use App\Models\StockItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\CashSessionService;
use App\Services\ReportService;
use App\Services\SaleService;
use App\Support\BusinessDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SupplierPayrollTest extends TestCase
{
    use RefreshDatabase;

    private function month(): string
    {
        return Carbon::parse(BusinessDay::today())->format('Y-m');
    }

    public function test_a_credit_delivery_raises_the_debt_without_touching_the_cash(): void
    {
        $manager = User::factory()->gerant()->create();
        $session = CashSession::factory()->create(['user_id' => $manager->id, 'opening_float' => 10000]);
        $supplier = Supplier::create(['name' => 'Boulangerie Sidi']);
        $pain = StockItem::factory()->create(['name' => 'Pain arabe', 'quantity' => 0, 'unit_cost' => 0, 'pack_quantity' => 10]);

        $this->actingAs($manager)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'spent_at' => BusinessDay::today(),
            'payment_method' => 'credit',
            'lines' => [['stock_item_id' => $pain->id, 'pack' => 1, 'quantity' => 20, 'total_price' => 800]],
        ])->assertRedirect();

        // Le stock entre, la dette monte, la caisse ne bouge pas.
        $this->assertEquals(200.0, (float) $pain->fresh()->quantity);
        $this->assertEquals(800.0, $supplier->fresh()->balance());
        $this->assertEquals(10000.0, app(CashSessionService::class)->expectedCash($session));
    }

    public function test_paying_the_invoice_clears_the_debt_and_takes_money_from_the_cash(): void
    {
        $manager = User::factory()->gerant()->create();
        $session = CashSession::factory()->create(['user_id' => $manager->id, 'opening_float' => 10000]);
        $supplier = Supplier::create(['name' => 'Boucherie']);
        $item = StockItem::factory()->create(['quantity' => 0, 'unit_cost' => 0, 'pack_quantity' => null]);

        $this->actingAs($manager)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'spent_at' => BusinessDay::today(),
            'payment_method' => 'credit',
            'lines' => [['stock_item_id' => $item->id, 'quantity' => 10, 'total_price' => 5000]],
        ]);

        $this->actingAs($manager)->post(route('suppliers.pay', $supplier), [
            'amount' => 3000,
            'payment_method' => 'especes',
            'paid_at' => BusinessDay::today(),
        ])->assertRedirect();

        $this->assertEquals(2000.0, $supplier->fresh()->balance());
        $this->assertEquals(7000.0, app(CashSessionService::class)->expectedCash($session));
        // Le règlement n'est PAS une dépense de plus : la marchandise était déjà comptée.
        $this->assertEquals(1, Expense::count());

        $this->actingAs($manager)->get(route('suppliers.show', $supplier))
            ->assertOk()
            ->assertSee('Boucherie')
            ->assertSee('À payer (total dû)');
    }

    public function test_a_credit_delivery_never_applies_to_a_sale(): void
    {
        $cashier = User::factory()->caissier()->create();
        $product = Product::factory()->create(['sale_price' => 100]);

        $this->actingAs($cashier)->post(route('sales.store'), [
            'payment_method' => 'credit',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('payment_method');
    }

    public function test_the_purchases_report_groups_by_day_supplier_and_article(): void
    {
        $manager = User::factory()->gerant()->create();
        $supplier = Supplier::create(['name' => 'Boulangerie Sidi']);
        $pain = StockItem::factory()->create(['name' => 'Pain arabe', 'quantity' => 0, 'unit_cost' => 0, 'pack_quantity' => 10]);

        $this->actingAs($manager)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'spent_at' => BusinessDay::today(),
            'payment_method' => 'credit',
            'lines' => [['stock_item_id' => $pain->id, 'pack' => 1, 'quantity' => 20, 'total_price' => 800]],
        ]);

        $report = app(ReportService::class)->purchases(BusinessDay::today(), BusinessDay::today());

        $this->assertEquals(800.0, $report['total']);
        $this->assertEquals(800.0, $report['creditTotal']);
        $this->assertEquals(0.0, $report['paidTotal']);
        $this->assertEquals('Boulangerie Sidi', $report['bySupplier'][0]['name']);
        $this->assertEquals(200.0, (float) $report['byArticle'][0]->qty);
        $this->assertEquals(800.0, (float) $report['byArticle'][0]->value);

        $this->actingAs($manager)->get(route('reports.purchases', ['period' => 'day']))
            ->assertOk()
            ->assertSee('Pain arabe')
            ->assertSee('Boulangerie Sidi');
    }

    public function test_salaries_are_tracked_paid_or_unpaid_by_month(): void
    {
        $admin = User::factory()->admin()->create();
        $session = CashSession::factory()->create(['user_id' => $admin->id, 'opening_float' => 50000]);
        $cook = Staff::create(['name' => 'Aminata', 'job_title' => 'Cuisinière', 'monthly_salary' => 15000]);
        $month = $this->month();

        $this->actingAs($admin)->get(route('staff.index'))
            ->assertOk()
            ->assertSee('Aminata')
            ->assertSee('Cuisinière')
            ->assertSee('Non payé');

        $this->actingAs($admin)->post(route('staff.pay', $cook), [
            'amount' => 10000,
            'payment_method' => 'especes',
            'month' => $month,
        ])->assertRedirect();

        $this->assertEquals(10000.0, $cook->paidFor($month));
        $this->assertFalse($cook->isPaidFor($month));
        $this->actingAs($admin)->get(route('staff.index'))->assertOk()->assertSee('Partiel');

        $this->actingAs($admin)->post(route('staff.pay', $cook), [
            'amount' => 5000,
            'payment_method' => 'especes',
            'month' => $month,
        ]);

        $this->assertTrue($cook->fresh()->isPaidFor($month));
        // Les salaires sortent de la caisse et comptent dans les dépenses.
        $this->assertEquals(35000.0, app(CashSessionService::class)->expectedCash($session));
        $this->assertEquals(15000.0, (float) Expense::where('staff_id', $cook->id)->sum('amount'));
    }

    public function test_the_treasury_report_shows_money_debts_stock_and_budgets(): void
    {
        $manager = User::factory()->gerant()->create();
        $month = $this->month();

        // Une vente de 10 000, une livraison à crédit de 800, un salaire dû de 15 000.
        $product = Product::factory()->create(['sale_price' => 10000]);
        app(SaleService::class)->record($manager, [['product_id' => $product->id, 'quantity' => 1]], PaymentMethod::Especes, null);

        $supplier = Supplier::create(['name' => 'Boulangerie Sidi']);
        $pain = StockItem::factory()->create(['name' => 'Pain arabe', 'quantity' => 0, 'unit_cost' => 0, 'pack_quantity' => 10]);
        $this->actingAs($manager)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'spent_at' => BusinessDay::today(),
            'payment_method' => 'credit',
            'lines' => [['stock_item_id' => $pain->id, 'pack' => 1, 'quantity' => 20, 'total_price' => 800]],
        ]);
        Staff::create(['name' => 'Aminata', 'job_title' => 'Cuisinière', 'monthly_salary' => 15000]);

        $report = app(ReportService::class)->treasury($month);

        $this->assertEquals(10000.0, $report['cashIn']);
        $this->assertEquals(0.0, $report['cashOut']);       // rien n'est encore sorti : tout est à crédit
        $this->assertEquals(800.0, $report['creditExpenses']);
        $this->assertEquals(800.0, $report['supplierDebt']);
        $this->assertEquals(15000.0, $report['salaryDue']);
        $this->assertEquals(800.0, $report['stockValue']);  // 200 pains × 4 MRU
        $this->assertEquals(800.0 - 800.0 - 15000.0, $report['workingCapital']);

        // Plafond de dépense : la catégorie « achats » est déjà consommée à 800.
        $this->actingAs($manager)->post(route('reports.budgets'), [
            'month' => $month,
            'budgets' => ['achat_marchandises' => 500],
        ])->assertRedirect();

        $this->assertEquals(500.0, (float) Budget::firstOrFail()->amount);

        $this->actingAs($manager)->get(route('reports.treasury'))
            ->assertOk()
            ->assertSee('Fonds de roulement')
            ->assertSee('Dépassé de 300 MRU');
    }

    public function test_a_cashier_cannot_reach_suppliers_or_salaries(): void
    {
        $cashier = User::factory()->caissier()->create();

        $this->actingAs($cashier)->get(route('suppliers.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('staff.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('reports.treasury'))->assertForbidden();
    }
}
