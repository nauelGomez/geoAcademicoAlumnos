<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'direccion' => 'required|string|max:255',
            'telefono' => 'required|string|max:50',
            'fdn' => 'required|date',
            'pass' => 'nullable|string|min:6',
            'pass2' => 'nullable|same:pass',
        ];
    }

    public function messages()
    {
        return [
            'pass2.same' => 'Las contraseñas no coinciden.',
        ];
    }
}
