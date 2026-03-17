<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Sin auth por ahora
    }

    public function rules()
    {
        return [
            'alumno_id' => 'required|integer|exists:alumnos,ID',
            'mail'      => 'nullable|email' // Opcional: si necesitás filtrar envíos por el mail del padre
        ];
    }

    public function messages()
    {
        return [
            'alumno_id.required' => 'El ID del alumno es obligatorio.',
            'alumno_id.exists'   => 'El alumno indicado no existe en la base de datos.',
        ];
    }
}