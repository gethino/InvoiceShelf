<?php

namespace App\Http\Controllers\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Response;

class PublicInvoicePdfController extends Controller
{
    public function __invoke(Invoice $invoice): Response
    {
        return $invoice->getGeneratedPDFOrStream('invoice');
    }
}
