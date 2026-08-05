<?php

namespace App\Http\Controllers\Company\Customer;

use App\Domains\Contacts\Models\Customer;
use App\Domains\Reporting\Queries\CustomerStatementQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Http\Requests\DeleteCustomersRequest;
use App\Http\Resources\CustomerResource;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomersController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService,
        private readonly CustomerStatementQuery $customerStatementQuery,
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Customer::class);

        $limit = $request->has('limit') ? $request->limit : 10;

        $customers = Customer::with('creator')
            ->whereCompany()
            ->applyFilters($request->all())
            ->paginateData($limit);

        $this->customerStatementQuery->hydrateAccountSummaries(
            $customers instanceof LengthAwarePaginator ? $customers->getCollection() : $customers
        );

        return CustomerResource::collection($customers)
            ->additional(['meta' => [
                'customer_total_count' => Customer::whereCompany()->count(),
            ]]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Requests\CustomerRequest $request)
    {
        $this->authorize('create', Customer::class);

        $customer = $this->customerService->create($request);
        $this->customerStatementQuery->hydrateAccountSummaries([$customer]);

        return new CustomerResource($customer);
    }

    /**
     * Display the specified resource.
     *
     * @return JsonResponse
     */
    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);

        $this->customerStatementQuery->hydrateAccountSummaries([$customer]);

        return new CustomerResource($customer);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function update(Requests\CustomerRequest $request, Customer $customer)
    {
        $this->authorize('update', $customer);

        $customer = $this->customerService->update($request, $customer);
        $this->customerStatementQuery->hydrateAccountSummaries([$customer]);

        return new CustomerResource($customer);
    }

    /**
     * Remove a list of Customers along side all their resources (ie. Estimates, Invoices, Payments and Addresses)
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function delete(DeleteCustomersRequest $request)
    {
        $this->authorize('delete multiple customers');

        $ids = Customer::whereCompany()
            ->whereIn('id', $request->ids)
            ->pluck('id');

        $this->customerService->delete($ids);

        return response()->json([
            'success' => true,
        ]);
    }
}
