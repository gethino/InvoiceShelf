<?php

namespace Modules\TripoliCustomizations\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomizationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $company = Company::find($this->header('company'));

        return $company !== null && $this->user()?->can('manage company', $company) === true;
    }

    public function rules(): array
    {
        return [
            'brand_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'theme_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'taxes_enabled' => ['required', 'boolean'],
            'use_on_login' => ['required', 'boolean'],
            'simplified_login' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'brand_color.regex' => 'The brand color must be a six-digit hex color.',
            'theme_color.regex' => 'The theme color must be a six-digit hex color.',
            'taxes_enabled.boolean' => 'The tax setting must be enabled or disabled.',
            'use_on_login.boolean' => 'The login brand setting must be enabled or disabled.',
            'simplified_login.boolean' => 'The simplified login setting must be enabled or disabled.',
        ];
    }
}
