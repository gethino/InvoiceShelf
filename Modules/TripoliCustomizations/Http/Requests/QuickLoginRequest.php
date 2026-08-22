<?php

namespace Modules\TripoliCustomizations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuickLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:4096'],
            'password' => ['required', 'string'],
        ];
    }
}
