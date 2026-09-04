<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Rules\Base64Mime;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyBrandingAssetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $companyId = (int) $this->header('company');

        return array_key_exists((string) $this->route('asset'), Company::DOCUMENT_BRANDING_COLLECTIONS)
            && ($this->user()?->ownsCompany($companyId) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'asset_data' => ['nullable', 'required_unless:remove,true', new Base64Mime(['gif', 'jpg', 'jpeg', 'png', 'webp'])],
            'remove' => ['nullable', 'boolean'],
        ];
    }
}
