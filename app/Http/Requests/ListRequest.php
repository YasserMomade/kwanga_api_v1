<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
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
            'designation' => 'required|string|max:250',
            'type' => 'required|in:entry,action',
        ];
    }

    public function messages(): array
    {
        return [
            'designation.required' => 'Este campo e obrigatorio',
            'designation.string' => 'A designação deve ser um texto válido.',
            'designation.max' => 'A designação não pode ter mais de 250 caracteres.',
            'type.required' => 'Este campo e obrigatorio',
        ];
    }
}
