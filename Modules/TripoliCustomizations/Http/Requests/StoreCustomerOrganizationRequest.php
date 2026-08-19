<?php

namespace Modules\TripoliCustomizations\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $company = Company::find($this->header('company'));

        return $company !== null && $this->user()?->can('manage company', $company) === true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('customer_organizations')->where('company_id', $this->header('company')),
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'An organization name is required.',
            'name.unique' => 'This company already has an organization with that name.',
        ];
    }
}
