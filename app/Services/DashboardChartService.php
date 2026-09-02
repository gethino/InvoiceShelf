<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardChartService
{
    /**
     * @return array{
     *     labels: array<int, int>,
     *     invoice_totals: array<int, int>,
     *     expense_totals: array<int, int>,
     *     receipt_totals: array<int, int>,
     *     net_income_totals: array<int, int>,
     *     total_sales: int,
     *     total_expenses: int,
     *     total_receipts: int,
     *     total_net_income: int
     * }
     */
    public function getCurrentMonth(?int $customerId = null): array
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $invoiceQuery = Invoice::query()->whereCompany();
        $expenseQuery = Expense::query()->whereCompany();
        $paymentQuery = Payment::query()->whereCompany();

        if ($customerId !== null) {
            $invoiceQuery->whereCustomer($customerId);
            $expenseQuery->whereUser($customerId);
            $paymentQuery->whereCustomer($customerId);
        }

        $invoiceTotalsByDate = $this->getTotalsByDate(
            $invoiceQuery,
            'invoice_date',
            'base_total',
            $startDate,
            $endDate
        );
        $expenseTotalsByDate = $this->getTotalsByDate(
            $expenseQuery,
            'expense_date',
            'base_amount',
            $startDate,
            $endDate
        );
        $receiptTotalsByDate = $this->getTotalsByDate(
            $paymentQuery,
            'payment_date',
            'base_amount',
            $startDate,
            $endDate
        );

        $labels = range(1, $startDate->daysInMonth);
        $invoiceTotals = $this->fillDailyTotals($labels, $startDate, $invoiceTotalsByDate);
        $expenseTotals = $this->fillDailyTotals($labels, $startDate, $expenseTotalsByDate);
        $receiptTotals = $this->fillDailyTotals($labels, $startDate, $receiptTotalsByDate);
        $netIncomeTotals = array_map(
            fn (int $receipts, int $expenses): int => $receipts - $expenses,
            $receiptTotals,
            $expenseTotals
        );

        $totalSales = array_sum($invoiceTotals);
        $totalExpenses = array_sum($expenseTotals);
        $totalReceipts = array_sum($receiptTotals);

        return [
            'labels' => $labels,
            'invoice_totals' => $invoiceTotals,
            'expense_totals' => $expenseTotals,
            'receipt_totals' => $receiptTotals,
            'net_income_totals' => $netIncomeTotals,
            'total_sales' => $totalSales,
            'total_expenses' => $totalExpenses,
            'total_receipts' => $totalReceipts,
            'total_net_income' => $totalReceipts - $totalExpenses,
        ];
    }

    /**
     * @return Collection<string, int>
     */
    private function getTotalsByDate(
        Builder $query,
        string $dateColumn,
        string $amountColumn,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        return $query
            ->selectRaw("DATE({$dateColumn}) as chart_date, SUM({$amountColumn}) as aggregate")
            ->whereBetween($dateColumn, [$startDate->toDateTimeString(), $endDate->toDateTimeString()])
            ->groupByRaw("DATE({$dateColumn})")
            ->pluck('aggregate', 'chart_date')
            ->map(fn (mixed $total): int => (int) $total);
    }

    /**
     * @param  array<int, int>  $labels
     * @param  Collection<string, int>  $totalsByDate
     * @return array<int, int>
     */
    private function fillDailyTotals(array $labels, Carbon $startDate, Collection $totalsByDate): array
    {
        return array_map(
            fn (int $day): int => $totalsByDate->get(
                $startDate->copy()->day($day)->toDateString(),
                0
            ),
            $labels
        );
    }
}
