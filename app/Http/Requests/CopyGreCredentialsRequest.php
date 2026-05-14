<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CopyGreCredentialsRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'origen' => [
                'required',
                'string',
                Rule::in(['beta', 'produccion'])
            ],
            'destino' => [
                'required',
                'string',
                Rule::in(['beta', 'produccion']),
                'different:origen'
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'origen.required' => 'El ambiente de origen es obligatorio.',
            'origen.in' => 'El ambiente de origen debe ser beta o produccion.',
            
            'destino.required' => 'El ambiente de destino es obligatorio.',
            'destino.in' => 'El ambiente de destino debe ser beta o produccion.',
            'destino.different' => 'El ambiente de destino debe ser diferente al de origen.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'origen' => 'ambiente de origen',
            'destino' => 'ambiente de destino',
        ];
    }
}