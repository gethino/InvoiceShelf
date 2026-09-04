<?php

namespace App\Http\Requests;

use App\Services\DocumentTemplateService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentTemplateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->ownsCompany((int) $this->header('company')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $templates = app(DocumentTemplateService::class);
        $invoiceNames = array_column($templates->catalog(DocumentTemplateService::INVOICE, ''), 'name');
        $estimateNames = array_column($templates->catalog(DocumentTemplateService::ESTIMATE, ''), 'name');

        return [
            'allowed_invoice_templates' => ['required', 'array', 'min:1'],
            'allowed_invoice_templates.*' => ['required', 'string', 'distinct', Rule::in($invoiceNames)],
            'default_invoice_template' => ['required', 'string', Rule::in($this->input('allowed_invoice_templates', []))],
            'allowed_estimate_templates' => ['required', 'array', 'min:1'],
            'allowed_estimate_templates.*' => ['required', 'string', 'distinct', Rule::in($estimateNames)],
            'default_estimate_template' => ['required', 'string', Rule::in($this->input('allowed_estimate_templates', []))],
        ];
    }
}
