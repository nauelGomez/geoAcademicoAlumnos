<?php

namespace App\Http\Requests\AppFamilias;

use Illuminate\Foundation\Http\FormRequest;

class ResolverTareaFamiliaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'resolucion' => 'nullable|string',
            'archivos' => 'nullable|array',
            'archivos.*' => 'file|max:15360|mimes:png,jpg,jpeg,webp,doc,docx,xls,xlsx,pdf,ppt,pptx,mp3,mp4,mov,zip',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $texto = trim((string) $this->input('resolucion', ''));
            $archivos = $this->file('archivos', []);

            if ($texto === '' && empty($archivos)) {
                $validator->errors()->add('resolucion', 'Debés enviar una resolución o al menos un archivo.');
            }
        });
    }

    public function messages()
    {
        return [
            'archivos.array' => 'El campo archivos debe ser un array.',
            'archivos.*.file' => 'Cada adjunto debe ser un archivo válido.',
            'archivos.*.max' => 'Cada archivo puede pesar hasta 15 MB.',
            'archivos.*.mimes' => 'Uno de los archivos tiene un formato no permitido.',
        ];
    }
}
