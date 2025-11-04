<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LifeAreaRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'id' => 'required|uuid',
            'designation' => 'required|string|max:55',
            'icon_path' => 'required',
        ];
    }


    public function messages(): array
    {
        return [
            'id.required' => 'O campo ID é obrigatório.',
            'id.uuid' => 'O ID é  invalido',
            'designation.required' => 'A designação é obrigatória.',
            'icon_path.required' => 'O ícone é obrigatório.',
        ];
    }
}
