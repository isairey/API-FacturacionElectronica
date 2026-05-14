<?php

namespace App\Http\Requests\Branch;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $branchId = $this->route('branch');

        return [
            'company_id' => 'sometimes|integer|exists:companies,id',
            'codigo' => 'sometimes|string|max:10',
            'nombre' => 'sometimes|string|max:255',
            'direccion' => 'sometimes|string|max:255',
            'ubigeo' => 'sometimes|string|size:6',
            'distrito' => 'sometimes|string|max:100',
            'provincia' => 'sometimes|string|max:100',
            'departamento' => 'sometimes|string|max:100',
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
            // Validar que el código sea único por empresa (excluyendo la sucursal actual)
            if ($this->has('codigo') && $this->has('company_id')) {
                $branchId = $this->route('branch');
                $exists = Branch::where('company_id', $this->input('company_id'))
                                          ->where('codigo', $this->input('codigo'))
                                          ->where('id', '!=', $branchId)
                                          ->exists();

                if ($exists) {
                    $validator->errors()->add('codigo', 'El código de sucursal ya existe para esta empresa.');
                }
            }

            // Si se está cambiando de empresa, validar que la nueva empresa existe
            if ($this->has('company_id')) {
                $branchId = $this->route('branch');
                $branch = \App\Models\Branch::find($branchId);
                
                if ($branch && $branch->company_id != $this->input('company_id')) {
                    // Verificar si hay documentos asociados que impidan el cambio
                    $hasDocuments = $branch->invoices()->exists() || 
                                  $branch->boletas()->exists() ||
                                  $branch->creditNotes()->exists() ||
                                  $branch->debitNotes()->exists();
                    
                    if ($hasDocuments) {
                        $validator->errors()->add('company_id', 'No se puede cambiar de empresa porque la sucursal tiene documentos asociados.');
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'company_id.integer' => 'La empresa debe ser un número entero.',
            'company_id.exists' => 'La empresa seleccionada no existe.',
            'codigo.max' => 'El código no puede tener más de 10 caracteres.',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
            'direccion.max' => 'La dirección no puede tener más de 255 caracteres.',
            'ubigeo.size' => 'El ubigeo debe tener exactamente 6 caracteres.',
            'distrito.max' => 'El distrito no puede tener más de 100 caracteres.',
            'provincia.max' => 'La provincia no puede tener más de 100 caracteres.',
            'departamento.max' => 'El departamento no puede tener más de 100 caracteres.',
            'telefono.max' => 'El teléfono no puede tener más de 20 caracteres.',
            'email.email' => 'El email debe tener un formato válido.',
            'email.max' => 'El email no puede tener más de 255 caracteres.',
            'contacto_nombre.max' => 'El nombre del contacto no puede tener más de 255 caracteres.',
            'activo.boolean' => 'El estado activo debe ser verdadero o falso.',
        ];
    }
}