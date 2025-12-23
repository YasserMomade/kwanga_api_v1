<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginVerifyOtpRequest extends FormRequest
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
            'phone' => ['required', 'string', 'max:20'],
            'code'  => ['required', 'digits:6'],
        ];
    }
}
