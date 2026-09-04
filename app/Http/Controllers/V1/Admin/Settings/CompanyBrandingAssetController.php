<?php

namespace App\Http\Controllers\V1\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCompanyBrandingAssetRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

class CompanyBrandingAssetController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdateCompanyBrandingAssetRequest $request, string $asset): JsonResponse
    {
        $company = Company::query()->findOrFail($request->header('company'));
        $collection = Company::DOCUMENT_BRANDING_COLLECTIONS[$asset];

        if ($request->boolean('remove')) {
            $company->clearMediaCollection($collection);
        }

        if ($request->filled('asset_data')) {
            $data = json_decode($request->string('asset_data')->toString(), false, 512, JSON_THROW_ON_ERROR);

            $company->addMediaFromBase64($data->data)
                ->usingFileName($data->name)
                ->toMediaCollection($collection);
        }

        return response()->json([
            'asset' => $asset,
            'url' => $company->fresh()->documentBrandingAssetUrl($asset),
        ]);
    }
}
