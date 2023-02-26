<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostStoreRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:400'],
            'content' => ['required', 'string'],
            'rating' => ['required', 'numeric', 'between:-99.99,99.99'],
            'count' => ['required', 'numeric', 'gt:0', 'between:0,99999999'],
        ];
    }
}
