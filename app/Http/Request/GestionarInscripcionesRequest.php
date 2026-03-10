<?php
// app/Http/Requests/GestionarInscripcionRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GestionarInscripcionRequest extends FormRequest
{
    public function authorize()
    {
        return true; 
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
            'id_materia_grupal.required' => 'Debe seleccionar un grupo.',
            'id_materia_grupal.exists' => 'El grupo seleccionado no es válido en el sistema.'
        ];
    }
}