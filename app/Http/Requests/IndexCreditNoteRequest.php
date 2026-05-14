<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexCreditNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'estado_sunat' => 'nullable|string|in:PENDIENTE,PROCESANDO,ACEPTADO,RECHAZADO',
            'tipo_doc_afectado' => 'nullable|string|in:01,03,07,08',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.exists' => 'La empresa seleccionada no existe.',
            'branch_id.exists' => 'La sucursal seleccionada no existe.',
            'estado_sunat.in' => 'El estado SUNAT debe ser: PENDIENTE, PROCESANDO, ACEPTADO o RECHAZADO.',
            'tipo_doc_afectado.in' => 'El tipo de documento afectado debe ser válido (01=Factura, 03=Boleta, 07=Nota Crédito, 08=Nota Débito).',
            'fecha_desde.date' => 'La fecha desde debe ser una fecha válida.',
            'fecha_hasta.date' => 'La fecha hasta debe ser una fecha válida.',
            'fecha_hasta.after_or_equal' => 'La fecha hasta debe ser igual o posterior a la fecha desde.',
            'per_page.integer' => 'Los elementos por página deben ser un número entero.',
            'per_page.min' => 'Debe mostrar al menos 1 elemento por página.',
            'per_page.max' => 'No se pueden mostrar más de 100 elementos por página.',
        ];
    }
}