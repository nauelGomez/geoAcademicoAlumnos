<?php
namespace App\Http\Requests\AppFamilias;
use Illuminate\Foundation\Http\FormRequest;

class ViewReportRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id'       => 'required|integer',
            'url_type' => 'required|string',
            'code'     => 'nullable|string'
        ];
    }

    public function messages()
    {
        return [
            'id.required'       => 'El identificador del informe es obligatorio.',
            'url_type.required' => 'El tipo de URL es obligatorio para resolver el informe.'
        ];
    }
}
