<?php

namespace App\Http\Controllers\V1\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDocumentTemplateSettingsRequest;
use App\Models\Company;
use App\Services\DocumentTemplateService;
use Illuminate\Http\JsonResponse;

class DocumentTemplateSettingsController extends Controller
{
    public function show(DocumentTemplateService $templates): JsonResponse
    {
        $company = Company::query()->findOrFail(request()->header('company'));
        $this->authorize('manage company', $company);

        return response()->json([
            'invoice_templates' => $templates->catalog(DocumentTemplateService::INVOICE),
            'estimate_templates' => $templates->catalog(DocumentTemplateService::ESTIMATE),
            'settings' => $templates->configuration($company->id),
        ]);
    }

    public function update(
        UpdateDocumentTemplateSettingsRequest $request,
        DocumentTemplateService $templates
    ): JsonResponse {
        $templates->save((int) $request->header('company'), $request->validated());

        return response()->json([
            'success' => true,
            'settings' => $templates->configuration((int) $request->header('company')),
        ]);
    }
}
