<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGreCredentialsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Aquí podrías agregar lógica de autorización específica
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'modo' => [
                'required',
                'string',
                Rule::in(['beta', 'produccion'])
            ],
            'client_id' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\-_]+$/', // Solo caracteres alfanuméricos, guiones y guiones bajos
            ],
            'client_secret' => [
                'required',
                'string',
                'max:255',
                'min:10', // Mínima seguridad para el secret
            ],
            'ruc_proveedor' => [
                'nullable',
                'string',
                'size:11',
                'regex:/^[0-9]{11}$/', // Exactamente 11 dígitos numéricos
                'different:20000000000', // No permitir RUC genérico
            ],
            'usuario_sol' => [
                'nullable',
                'string',
                'max:100',
                'min:3',
                'regex:/^[a-zA-Z0-9\-_\.]+$/', // Caracteres alfanuméricos y algunos especiales
            ],
            'clave_sol' => [
                'nullable',
                'string',
                'max:100',
                'min:6', // Mínima longitud para la clave
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'modo.required' => 'El modo de ambiente es obligatorio.',
            'modo.in' => 'El modo debe ser beta o produccion.',
            
            'client_id.required' => 'El Client ID es obligatorio.',
            'client_id.max' => 'El Client ID no debe exceder 255 caracteres.',
            'client_id.regex' => 'El Client ID solo puede contener letras, números, guiones y guiones bajos.',
            
            'client_secret.required' => 'El Client Secret es obligatorio.',
            'client_secret.max' => 'El Client Secret no debe exceder 255 caracteres.',
            'client_secret.min' => 'El Client Secret debe tener al menos 10 caracteres.',
            
            'ruc_proveedor.size' => 'El RUC debe tener exactamente 11 dígitos.',
            'ruc_proveedor.regex' => 'El RUC debe contener solo números.',
            'ruc_proveedor.different' => 'El RUC no puede ser genérico.',
            
            'usuario_sol.min' => 'El usuario SOL debe tener al menos 3 caracteres.',
            'usuario_sol.max' => 'El usuario SOL no debe exceder 100 caracteres.',
            'usuario_sol.regex' => 'El usuario SOL contiene caracteres no válidos.',
            
            'clave_sol.min' => 'La clave SOL debe tener al menos 6 caracteres.',
            'clave_sol.max' => 'La clave SOL no debe exceder 100 caracteres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'modo' => 'ambiente',
            'client_id' => 'Client ID',
            'client_secret' => 'Client Secret',
            'ruc_proveedor' => 'RUC del proveedor',
            'usuario_sol' => 'usuario SOL',
            'clave_sol' => 'clave SOL',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Si es ambiente de producción, validar que se proporcionen credenciales específicas
            if ($this->input('modo') === 'produccion') {
                if (empty($this->input('ruc_proveedor'))) {
                    $validator->errors()->add('ruc_proveedor', 'El RUC del proveedor es obligatorio en producción.');
                }
                
                if (empty($this->input('usuario_sol'))) {
                    $validator->errors()->add('usuario_sol', 'El usuario SOL es obligatorio en producción.');
                }
                
                if (empty($this->input('clave_sol'))) {
                    $validator->errors()->add('clave_sol', 'La clave SOL es obligatoria en producción.');
                }
            }

            // Validar que las credenciales de beta no se usen en producción
            if ($this->input('modo') === 'produccion') {
                $defaultBetaCredentials = [
                    'test-85e5b0ae-255c-4891-a595-0b98c65c9854',
                    'test-Hty/M6QshYvPgItX2P0+Kw==',
                    '20161515648',
                    'MODDATOS'
                ];

                if (in_array($this->input('client_id'), $defaultBetaCredentials) ||
                    in_array($this->input('client_secret'), $defaultBetaCredentials) ||
                    in_array($this->input('ruc_proveedor'), $defaultBetaCredentials) ||
                    in_array($this->input('usuario_sol'), $defaultBetaCredentials)) {
                    
                    $validator->errors()->add('modo', 'No se pueden usar credenciales de prueba en ambiente de producción.');
                }
            }
        });
    }
}