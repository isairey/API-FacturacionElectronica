<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ajusta si necesitas lógica de autorización
    }

    public function rules(): array
    {
        $companyId = $this->route('company')->id ?? null;

        return [
            'ruc' => [
                'required',
                'string',
                'size:11',
                Rule::unique('companies', 'ruc')->ignore($companyId),
            ],
            'razon_social' => 'required|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'direccion' => 'required|string|max:255',
            'ubigeo' => 'required|string|size:6',
            'distrito' => 'required|string|max:100',
            'provincia' => 'required|string|max:100',
            'departamento' => 'required|string|max:100',
            'telefono' => 'nullable|string|max:20',
            'email' => 'required|email|max:255',
            'web' => 'nullable|url|max:255',
            'usuario_sol' => 'required|string|max:50',
            'clave_sol' => 'required|string|max:100',
            'certificado_pem' => 'nullable|file|mimes:pem,crt,cer,txt|max:2048',
            'certificado_password' => 'nullable|string|max:100',
            'endpoint_beta' => 'nullable|url|max:255',
            'endpoint_produccion' => 'nullable|url|max:255',
            'modo_produccion' => 'nullable|in:true,false,1,0',
            'logo_path' => 'nullable|file|mimes:png,jpeg,jpg|max:2048',
            'activo' => 'boolean'
        ];
    }
}
