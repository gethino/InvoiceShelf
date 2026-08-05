<?php

namespace App\Http\Controllers\Pdf;

use App\Domains\Sales\Models\Estimate;
use App\Domains\Sales\Models\Invoice;
use App\Http\Controllers\Controller;
use App\Services\Document\EstimateService;
use App\Services\Document\InvoiceService;
use Illuminate\Http\Request;

class DocumentPdfController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly EstimateService $estimateService,
    ) {}

    public function invoice(Request $request, Invoice $invoice)
    {
        if ($request->has('preview')) {
            return $this->invoiceService->getPdfData($invoice);
        }

        return $invoice->getGeneratedPDFOrStream('invoice');
    }

    public function estimate(Request $request, Estimate $estimate)
    {
        if ($request->has('preview')) {
            return $this->estimateService->getPdfData($estimate);
        }

        return $estimate->getGeneratedPDFOrStream('estimate');
    }
}
