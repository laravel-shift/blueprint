<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CertificateUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'certificate_type_id' => ['required', 'integer', 'exists:certificate_types,id'],
            'reference' => ['required', 'string'],
            'document' => ['required', 'string'],
            'expiry_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
