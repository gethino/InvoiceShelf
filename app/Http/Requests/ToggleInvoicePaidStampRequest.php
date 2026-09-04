<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Silber\Bouncer\BouncerFacade;

class ToggleInvoicePaidStampRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        return $invoice instanceof Invoice
            && $invoice->paid_status === Invoice::STATUS_PAID
            && ($this->user()?->hasCompany($invoice->company_id) ?? false)
            && BouncerFacade::can('edit-invoice', $invoice);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'show_paid_stamp' => ['required', 'boolean'],
        ];
    }
}
