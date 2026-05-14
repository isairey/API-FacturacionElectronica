<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Si quieres, puedes poner lógica para autorizar según el usuario.
        return true;
    }

    public function rules(): array
    {
        return [
            'ruc' => 'required|string|size:11|unique:companies,ruc',
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

    /**
     * Opcional: mensajes personalizados
     */
    public function messages(): array
    {
        return [
            'ruc.required' => 'El RUC es obligatorio',
            'ruc.size' => 'El RUC debe tener exactamente 11 dígitos',
            'ruc.unique' => 'El RUC ya está registrado',
            'email.email' => 'El correo debe tener un formato válido',
            'certificado_pem.mimes' => 'El certificado debe ser un archivo válido (.pem, .crt, .cer, .txt)',
            'logo_path.mimes' => 'El logo debe estar en formato PNG o JPG',
        ];
    }
}
