<?php

namespace App\Http\Controllers\V1\Admin\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\DashboardChartService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CustomerStatsController extends Controller
{
    public function __construct(private readonly DashboardChartService $dashboardChartService) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Customer $customer): CustomerResource
    {
        $this->authorize('view', $customer);

        if ($request->boolean('this_month')) {
            $currentMonthData = $this->dashboardChartService->getCurrentMonth($customer->id);
            $months = $currentMonthData['labels'];
            $invoiceTotals = $currentMonthData['invoice_totals'];
            $expenseTotals = $currentMonthData['expense_totals'];
            $receiptTotals = $currentMonthData['receipt_totals'];
            $netProfits = $currentMonthData['net_income_totals'];
            $salesTotal = $currentMonthData['total_sales'];
            $totalReceipts = $currentMonthData['total_receipts'];
            $totalExpenses = $currentMonthData['total_expenses'];
            $netProfit = $currentMonthData['total_net_income'];
        } else {
            $i = 0;
            $months = [];
            $invoiceTotals = [];
            $expenseTotals = [];
            $receiptTotals = [];
            $netProfits = [];
            $monthCounter = 0;
            $fiscalYear = CompanySetting::getSetting('fiscal_year', $request->header('company'));
            $startDate = Carbon::now();
            $start = Carbon::now();
            $end = Carbon::now();
            $terms = explode('-', $fiscalYear);
            $companyStartMonth = intval($terms[0]);

            if ($companyStartMonth <= $start->month) {
                $startDate->month($companyStartMonth)->startOfMonth();
                $start->month($companyStartMonth)->startOfMonth();
                $end->month($companyStartMonth)->endOfMonth();
            } else {
                $startDate->subYear()->month($companyStartMonth)->startOfMonth();
                $start->subYear()->month($companyStartMonth)->startOfMonth();
                $end->subYear()->month($companyStartMonth)->endOfMonth();
            }

            if ($request->has('previous_year')) {
                $startDate->subYear()->startOfMonth();
                $start->subYear()->startOfMonth();
                $end->subYear()->endOfMonth();
            }
            while ($monthCounter < 12) {
                array_push(
                    $invoiceTotals,
                    Invoice::whereBetween(
                        'invoice_date',
                        [$start->format('Y-m-d'), $end->format('Y-m-d')]
                    )
                        ->whereCompany()
                        ->whereCustomer($customer->id)
                        ->sum('base_total') ?? 0
                );
                array_push(
                    $expenseTotals,
                    Expense::whereBetween(
                        'expense_date',
                        [$start->format('Y-m-d'), $end->format('Y-m-d')]
                    )
                        ->whereCompany()
                        ->whereUser($customer->id)
                        ->sum('base_amount') ?? 0
                );
                array_push(
                    $receiptTotals,
                    Payment::whereBetween(
                        'payment_date',
                        [$start->format('Y-m-d'), $end->format('Y-m-d')]
                    )
                        ->whereCompany()
                        ->whereCustomer($customer->id)
                        ->sum('base_amount') ?? 0
                );
                array_push(
                    $netProfits,
                    ($receiptTotals[$i] - $expenseTotals[$i])
                );
                $i++;
                array_push($months, $start->translatedFormat('M'));
                $monthCounter++;
                $end->startOfMonth();
                $start->addMonth()->startOfMonth();
                $end->addMonth()->endOfMonth();
            }

            $start->subMonth()->endOfMonth();

            $salesTotal = Invoice::whereBetween(
                'invoice_date',
                [$startDate->format('Y-m-d'), $start->format('Y-m-d')]
            )
                ->whereCompany()
                ->whereCustomer($customer->id)
                ->sum('base_total');
            $totalReceipts = Payment::whereBetween(
                'payment_date',
                [$startDate->format('Y-m-d'), $start->format('Y-m-d')]
            )
                ->whereCompany()
                ->whereCustomer($customer->id)
                ->sum('base_amount');
            $totalExpenses = Expense::whereBetween(
                'expense_date',
                [$startDate->format('Y-m-d'), $start->format('Y-m-d')]
            )
                ->whereCompany()
                ->whereUser($customer->id)
                ->sum('base_amount');
            $netProfit = (int) $totalReceipts - (int) $totalExpenses;
        }

        $chartData = [
            'months' => $months,
            'invoiceTotals' => $invoiceTotals,
            'expenseTotals' => $expenseTotals,
            'receiptTotals' => $receiptTotals,
            'netProfit' => $netProfit,
            'netProfits' => $netProfits,
            'salesTotal' => $salesTotal,
            'totalReceipts' => $totalReceipts,
            'totalExpenses' => $totalExpenses,
        ];

        $customer = Customer::find($customer->id);

        return (new CustomerResource($customer))
            ->additional(['meta' => [
                'chartData' => $chartData,
            ]]);
    }
}
