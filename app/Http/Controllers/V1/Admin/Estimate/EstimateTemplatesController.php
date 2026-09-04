<?php

namespace App\Http\Controllers\V1\Admin\Estimate;

use App\Http\Controllers\Controller;
use App\Models\Estimate;
use App\Services\DocumentTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstimateTemplatesController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return JsonResponse
     */
    public function __invoke(Request $request, DocumentTemplateService $templates)
    {
        $this->authorize('viewAny', Estimate::class);

        $companyId = (int) $request->header('company');

        return response()->json([
            'estimateTemplates' => $templates->allowedTemplates(DocumentTemplateService::ESTIMATE, $companyId),
            'defaultTemplate' => $templates->defaultName(DocumentTemplateService::ESTIMATE, $companyId),
        ]);
    }
}
