<?php

namespace App\Http\Controllers\V1\Admin\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\DocumentTemplateService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceTemplatesController extends Controller
{
    /**
     * Handle the incoming request.
     *
     *
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function __invoke(Request $request, DocumentTemplateService $templates)
    {
        $this->authorize('viewAny', Invoice::class);

        $companyId = (int) $request->header('company');

        return response()->json([
            'invoiceTemplates' => $templates->allowedTemplates(DocumentTemplateService::INVOICE, $companyId),
            'defaultTemplate' => $templates->defaultName(DocumentTemplateService::INVOICE, $companyId),
        ]);
    }
}
