<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GestionarInscripcionRequest extends FormRequest
{
    public function authorize()
    {
        return true; // La lógica de sesión ya se maneja en el controller
    }

    public function rules()
    {
        return [
            'id_materia_grupal' => 'required|integer|exists:materias_grupales,ID'
        ];
    }

    public function messages()
    {
        return [
            'id_materia_grupal.required' => 'Debe seleccionar una materia para realizar la operación.',
            'id_materia_grupal.exists'   => 'El grupo seleccionado no existe o no es válido.'
        ];
    }
}