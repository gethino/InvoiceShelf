<?php

namespace App\Http\Controllers\CustomerPortal\Payment;

use App\Domains\Accounts\Models\Company;
use App\Domains\Receivables\Models\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\PaymentMethodResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentMethodController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return Response
     */
    public function __invoke(Request $request, Company $company)
    {
        return PaymentMethodResource::collection(PaymentMethod::where('company_id', $company->id)->get());
    }
}
