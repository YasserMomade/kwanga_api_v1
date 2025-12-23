<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskRequest extends FormRequest
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
            'list_id' => 'nullable|exists:lists,id',
            'description' => 'required|string|max:255',
            'completed' => 'boolean',
            'has_due_date' => 'boolean',
            'due_date' => 'nullable|date',
            'has_reminder' => 'boolean',
            'reminder_datetime' => 'nullable|date',
            'has_frequency' => 'boolean',
            'frequency_days' => 'nullable|array',
        ];
    }

    public function messages(): array
    {

        return [

            'list_id.required' => 'Selecione uma lista',

            'designation.required' => 'O campo "designação" é obrigatório.',
            'designation.max' => 'A designação não pode ter mais de 255 caracteres.',

        ];
    }
}
