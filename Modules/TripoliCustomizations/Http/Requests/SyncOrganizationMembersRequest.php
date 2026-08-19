<?php

namespace Modules\TripoliCustomizations\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncOrganizationMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        $company = Company::find($this->header('company'));

        return $company !== null && $this->user()?->can('manage company', $company) === true;
    }

    public function rules(): array
    {
        return [
            'customer_ids' => ['required', 'array'],
            'customer_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('customers', 'id')->where('company_id', $this->header('company')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_ids.required' => 'Choose the organization members.',
            'customer_ids.*.exists' => 'Every member must belong to the current company.',
        ];
    }
}
