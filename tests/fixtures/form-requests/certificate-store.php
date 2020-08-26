<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CertificateStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string'],
            'certificate_type_id' => ['required', 'integer', 'exists:certificate_types,id'],
            'reference' => ['required', 'string'],
            'document' => ['required', 'string'],
            'expiry_date' => ['required', 'date'],
            'remarks' => ['string'],
        ];
    }
}
