<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InscribirMateriaRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Validar auth() según tu middleware
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
            'id_materia_grupal.required' => 'Debe seleccionar un grupo para inscribirse.',
            'id_materia_grupal.exists' => 'El grupo seleccionado no es válido.'
        ];
    }
}