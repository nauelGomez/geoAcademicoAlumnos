<?php

namespace App\Http\Requests\AppFamilias;

use Illuminate\Foundation\Http\FormRequest;

class StoreWallInterventionRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado a hacer esta solicitud.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Obtener las reglas de validación que aplican a la solicitud.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'mensaje' => 'required|string|min:1|max:5000',
        ];
    }

    /**
     * Obtener los mensajes de validación personalizados.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'mensaje.required' => 'La intervención no puede estar vacía.',
            'mensaje.string' => 'La intervención debe ser un texto válido.',
            'mensaje.min' => 'La intervención debe contener al menos 1 carácter.',
            'mensaje.max' => 'La intervención no puede exceder 5000 caracteres.',
        ];
    }
}
