<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexVoidedDocumentRequest extends FormRequest
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
            'estado_sunat' => 'nullable|string|in:PENDIENTE,ENVIADO,PROCESANDO,ACEPTADO,RECHAZADO,ERROR',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
            'fecha_referencia' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.exists' => 'La empresa seleccionada no existe.',
            'branch_id.exists' => 'La sucursal seleccionada no existe.',
            'estado_sunat.in' => 'Estado SUNAT inválido.',
            'fecha_desde.date' => 'La fecha desde debe ser una fecha válida.',
            'fecha_hasta.date' => 'La fecha hasta debe ser una fecha válida.',
            'fecha_hasta.after_or_equal' => 'La fecha hasta debe ser mayor o igual a la fecha desde.',
            'fecha_referencia.date' => 'La fecha de referencia debe ser una fecha válida.',
            'per_page.integer' => 'Los elementos por página deben ser un número entero.',
            'per_page.min' => 'Mínimo 1 elemento por página.',
            'per_page.max' => 'Máximo 100 elementos por página.',
        ];
    }
}