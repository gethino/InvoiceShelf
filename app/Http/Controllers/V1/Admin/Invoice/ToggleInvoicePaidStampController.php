<?php

namespace App\Http\Controllers\V1\Admin\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\ToggleInvoicePaidStampRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;

class ToggleInvoicePaidStampController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ToggleInvoicePaidStampRequest $request, Invoice $invoice): InvoiceResource
    {
        $invoice->update($request->validated());

        return new InvoiceResource($invoice->fresh());
    }
}
