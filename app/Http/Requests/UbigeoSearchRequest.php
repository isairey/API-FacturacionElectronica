<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UbigeoSearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => 'required|string|min:2|max:255',
            'region_id' => 'nullable|string|size:6',
            'provincia_id' => 'nullable|string|size:6',
        ];
    }
    
    public function messages(): array
    {
        return [
            'q.required' => 'El campo de búsqueda es requerido',
            'q.min' => 'La búsqueda debe tener al menos 2 caracteres',
            'q.max' => 'La búsqueda no puede exceder 255 caracteres',
            'region_id.exists' => 'La región seleccionada no es válida',
            'provincia_id.exists' => 'La provincia seleccionada no es válida',
        ];
    }
}
