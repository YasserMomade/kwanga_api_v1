<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Nette\Utils\Arrays;

class PurposeRequest extends FormRequest
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
            'description' => 'required',
            'id' => 'required|uuid',
            'life_area_id' => 'required|exists:life_areas,id'
        ];
    }

    public function messages(): array
    {

        return [
            'id.required' => 'O campo ID é obrigatório.',
            'id.uuid' => 'O ID é  invalido',
            'life_area_id.exists' => 'A area da vida não existe.',
            'life_area_id.required' => 'A area da vida é obrigatória.',
        ];
    }
}
