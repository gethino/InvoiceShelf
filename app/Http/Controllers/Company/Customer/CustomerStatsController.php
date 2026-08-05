<?php

namespace App\Http\Controllers\Company\Customer;

use App\Domains\Contacts\Models\Customer;
use App\Domains\Reporting\Queries\CustomerStatementQuery;
use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Services\CustomerService;
use Illuminate\Http\Request;

class CustomerStatsController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService,
        private readonly CustomerStatementQuery $customerStatementQuery,
    ) {}

    public function __invoke(Request $request, Customer $customer)
    {
        $this->authorize('view', $customer);

        $chartData = $this->customerService->getStats(
            $customer,
            $request->header('company'),
            $request->has('previous_year')
        );

        $customer = Customer::find($customer->id);
        $this->customerStatementQuery->hydrateAccountSummaries([$customer]);

        return (new CustomerResource($customer))
            ->additional(['meta' => [
                'chartData' => $chartData,
            ]]);
    }
}
