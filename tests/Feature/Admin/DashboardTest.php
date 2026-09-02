<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $this->user = User::find(1);
    $this->company = $this->user->companies()->first();
    $this->withHeaders([
        'company' => $this->company->id,
    ]);
    Sanctum::actingAs(
        $this->user,
        ['*']
    );
});

getJson('api/v1/dashboard')->assertOk();

getJson('api/v1/search?name=ab')->assertOk();

test('this month charts return daily scoped totals for every calendar month length', function () {
    $customer = Customer::factory()->create();

    foreach ([
        ['2031-02-15', 28],
        ['2032-02-15', 29],
        ['2032-04-15', 30],
        ['2032-01-15', 31],
    ] as [$date, $dayCount]) {
        $this->travelTo(Carbon::parse($date));

        getJson('api/v1/dashboard?this_month=true')
            ->assertOk()
            ->assertJsonCount($dayCount, 'chart_data.months')
            ->assertJsonCount($dayCount, 'chart_data.invoice_totals')
            ->assertJsonCount($dayCount, 'chart_data.expense_totals')
            ->assertJsonCount($dayCount, 'chart_data.receipt_totals')
            ->assertJsonCount($dayCount, 'chart_data.net_income_totals')
            ->assertJsonPath('chart_data.months.0', 1)
            ->assertJsonPath('chart_data.months.'.($dayCount - 1), $dayCount);

        getJson("api/v1/customers/{$customer->id}/stats?this_month=true")
            ->assertOk()
            ->assertJsonCount($dayCount, 'meta.chartData.months')
            ->assertJsonCount($dayCount, 'meta.chartData.invoiceTotals')
            ->assertJsonCount($dayCount, 'meta.chartData.expenseTotals')
            ->assertJsonCount($dayCount, 'meta.chartData.receiptTotals')
            ->assertJsonCount($dayCount, 'meta.chartData.netProfits');
    }

    $this->travelTo(Carbon::parse('2032-01-10'));

    $otherCustomer = Customer::factory()->create();
    $otherCompany = Company::factory()->create();
    $otherCompanyCustomer = Customer::factory()->create(['company_id' => $otherCompany->id]);

    Invoice::factory()->create([
        'recurring_invoice_id' => null,
        'customer_id' => $customer->id,
        'invoice_date' => '2032-01-02',
        'base_total' => 1000,
    ]);
    Invoice::factory()->create([
        'recurring_invoice_id' => null,
        'customer_id' => $customer->id,
        'invoice_date' => '2032-01-20',
        'base_total' => 500,
    ]);
    Invoice::factory()->create([
        'recurring_invoice_id' => null,
        'customer_id' => $otherCustomer->id,
        'invoice_date' => '2032-01-02',
        'base_total' => 700,
    ]);
    Invoice::factory()->create([
        'recurring_invoice_id' => null,
        'company_id' => $otherCompany->id,
        'customer_id' => $otherCompanyCustomer->id,
        'invoice_date' => '2032-01-02',
        'base_total' => 9000,
    ]);
    Invoice::factory()->create([
        'recurring_invoice_id' => null,
        'customer_id' => $customer->id,
        'invoice_date' => '2031-12-31',
        'base_total' => 3000,
    ]);

    Payment::factory()->create([
        'customer_id' => $customer->id,
        'payment_date' => '2032-01-02',
        'base_amount' => 600,
    ]);
    Payment::factory()->create([
        'customer_id' => $customer->id,
        'payment_date' => '2032-01-20',
        'base_amount' => 200,
    ]);
    Payment::factory()->create([
        'customer_id' => $otherCustomer->id,
        'payment_date' => '2032-01-02',
        'base_amount' => 300,
    ]);

    Expense::factory()->create([
        'customer_id' => $customer->id,
        'expense_date' => '2032-01-02',
        'base_amount' => 100,
    ]);
    Expense::factory()->create([
        'customer_id' => $customer->id,
        'expense_date' => '2032-01-20',
        'base_amount' => 50,
    ]);
    Expense::factory()->create([
        'customer_id' => $otherCustomer->id,
        'expense_date' => '2032-01-02',
        'base_amount' => 25,
    ]);

    getJson('api/v1/dashboard?previous_year=true&this_month=true')
        ->assertOk()
        ->assertJsonPath('chart_data.invoice_totals.0', 0)
        ->assertJsonPath('chart_data.invoice_totals.1', 1700)
        ->assertJsonPath('chart_data.invoice_totals.19', 500)
        ->assertJsonPath('chart_data.receipt_totals.1', 900)
        ->assertJsonPath('chart_data.expense_totals.1', 125)
        ->assertJsonPath('chart_data.net_income_totals.1', 775)
        ->assertJsonPath('total_sales', 2200)
        ->assertJsonPath('total_receipts', 1100)
        ->assertJsonPath('total_expenses', 175)
        ->assertJsonPath('total_net_income', 925);

    getJson("api/v1/customers/{$customer->id}/stats?this_month=true")
        ->assertOk()
        ->assertJsonPath('meta.chartData.invoiceTotals.1', 1000)
        ->assertJsonPath('meta.chartData.invoiceTotals.19', 500)
        ->assertJsonPath('meta.chartData.receiptTotals.1', 600)
        ->assertJsonPath('meta.chartData.expenseTotals.1', 100)
        ->assertJsonPath('meta.chartData.netProfits.1', 500)
        ->assertJsonPath('meta.chartData.salesTotal', 1500)
        ->assertJsonPath('meta.chartData.totalReceipts', 800)
        ->assertJsonPath('meta.chartData.totalExpenses', 150)
        ->assertJsonPath('meta.chartData.netProfit', 650);
});
