<?php

namespace App\Http\Requests\Branch;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => 'required|integer|exists:companies,id',
            'codigo' => 'required|string|max:10',
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'ubigeo' => 'required|string|size:6',
            'distrito' => 'required|string|max:100',
            'provincia' => 'required|string|max:100',
            'departamento' => 'required|string|max:100',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'contacto_nombre' => 'nullable|string|max:255',
            'series_factura' => 'nullable|array',
            'series_boleta' => 'nullable|array',
            'series_nota_credito' => 'nullable|array',
            'series_nota_debito' => 'nullable|array',
            'series_guia_remision' => 'nullable|array',
            'activo' => 'sometimes|boolean',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que el código sea único por empresa
            $exists = Branch::where('company_id', $this->input('company_id'))
                                      ->where('codigo', $this->input('codigo'))
                                      ->exists();

            if ($exists) {
                $validator->errors()->add('codigo', 'El código de sucursal ya existe para esta empresa.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'La empresa es requerida.',
            'company_id.exists' => 'La empresa seleccionada no existe.',
            'codigo.required' => 'El código de la sucursal es requerido.',
            'codigo.max' => 'El código no puede tener más de 10 caracteres.',
            'nombre.required' => 'El nombre de la sucursal es requerido.',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
            'direccion.required' => 'La dirección es requerida.',
            'direccion.max' => 'La dirección no puede tener más de 255 caracteres.',
            'ubigeo.required' => 'El ubigeo es requerido.',
            'ubigeo.size' => 'El ubigeo debe tener exactamente 6 caracteres.',
            'distrito.required' => 'El distrito es requerido.',
            'distrito.max' => 'El distrito no puede tener más de 100 caracteres.',
            'provincia.required' => 'La provincia es requerida.',
            'provincia.max' => 'La provincia no puede tener más de 100 caracteres.',
            'departamento.required' => 'El departamento es requerido.',
            'departamento.max' => 'El departamento no puede tener más de 100 caracteres.',
            'telefono.max' => 'El teléfono no puede tener más de 20 caracteres.',
            'email.email' => 'El email debe tener un formato válido.',
            'email.max' => 'El email no puede tener más de 255 caracteres.',
            'contacto_nombre.max' => 'El nombre del contacto no puede tener más de 255 caracteres.',
            'activo.boolean' => 'El estado activo debe ser verdadero o falso.',
        ];
    }
}