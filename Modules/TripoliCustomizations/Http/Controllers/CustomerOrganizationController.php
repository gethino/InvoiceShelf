<?php

namespace Modules\TripoliCustomizations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Company;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\TripoliCustomizations\Entities\CustomerOrganization;
use Modules\TripoliCustomizations\Http\Requests\StoreCustomerOrganizationRequest;
use Modules\TripoliCustomizations\Http\Requests\SyncOrganizationMembersRequest;
use Modules\TripoliCustomizations\Http\Requests\UpdateCustomerOrganizationRequest;
use Modules\TripoliCustomizations\Transformers\CustomerOrganizationResource;

class CustomerOrganizationController extends Controller
{
    public function index(): JsonResponse
    {
        $companyId = $this->currentCompany()->id;

        $organizations = CustomerOrganization::query()
            ->where('company_id', $companyId)
            ->withCount('customers')
            ->with(['customers' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();

        $customers = Customer::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => CustomerOrganizationResource::collection($organizations),
            'customers' => CustomerResource::collection($customers),
        ]);
    }

    public function store(StoreCustomerOrganizationRequest $request): CustomerOrganizationResource
    {
        $organization = CustomerOrganization::query()->create([
            ...$request->validated(),
            'company_id' => (int) $request->header('company'),
        ]);

        return new CustomerOrganizationResource($organization->load('customers')->loadCount('customers'));
    }

    public function update(
        UpdateCustomerOrganizationRequest $request,
        CustomerOrganization $organization,
    ): CustomerOrganizationResource {
        $this->ensureCurrentCompany($organization);
        $organization->update($request->validated());

        return new CustomerOrganizationResource($organization->load('customers')->loadCount('customers'));
    }

    public function destroy(CustomerOrganization $organization): JsonResponse
    {
        $this->currentCompany();
        $this->ensureCurrentCompany($organization);
        $organization->delete();

        return response()->json(['success' => true]);
    }

    public function syncMembers(
        SyncOrganizationMembersRequest $request,
        CustomerOrganization $organization,
    ): CustomerOrganizationResource {
        $this->ensureCurrentCompany($organization);
        $customerIds = $request->validated('customer_ids');

        DB::transaction(function () use ($organization, $customerIds): void {
            Customer::query()
                ->where('company_id', $organization->company_id)
                ->where('customer_organization_id', $organization->id)
                ->whereNotIn('id', $customerIds)
                ->update(['customer_organization_id' => null]);

            Customer::query()
                ->where('company_id', $organization->company_id)
                ->whereIn('id', $customerIds)
                ->update(['customer_organization_id' => $organization->id]);
        });

        return new CustomerOrganizationResource($organization->load('customers')->loadCount('customers'));
    }

    private function ensureCurrentCompany(CustomerOrganization $organization): void
    {
        abort_unless($organization->company_id === (int) request()->header('company'), 403);
    }

    private function currentCompany(): Company
    {
        $company = Company::query()->findOrFail((int) request()->header('company'));
        $this->authorize('manage company', $company);

        return $company;
    }
}
