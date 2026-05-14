<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Branch;

class StoreVoidedDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'fecha_referencia' => 'required|date|before_or_equal:today|after_or_equal:' . now()->subDays(7)->toDateString(),
            'motivo_baja' => 'required|string|max:500',
            'ubl_version' => 'nullable|string|in:2.0,2.1',
            'usuario_creacion' => 'nullable|string|max:100',
            
            // Detalles de documentos a anular
            'detalles' => 'required|array|min:1|max:100',
            'detalles.*.tipo_documento' => 'required|string|in:01,03,07,08,09',
            'detalles.*.serie' => 'required|string|max:4',
            'detalles.*.correlativo' => 'required|string|max:8',
            'detalles.*.motivo_especifico' => 'required|string|max:250',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que la sucursal pertenece a la empresa
            $branch = Branch::where('id', $this->input('branch_id'))
                          ->where('company_id', $this->input('company_id'))
                          ->first();

            if (!$branch) {
                $validator->errors()->add('branch_id', 'La sucursal no pertenece a la empresa seleccionada.');
            }
            
            // Validar que no hay documentos duplicados
            $detalles = $this->input('detalles', []);
            $documentos = [];
            
            foreach ($detalles as $index => $detalle) {
                $key = $detalle['tipo_documento'] . '-' . $detalle['serie'] . '-' . $detalle['correlativo'];
                
                if (in_array($key, $documentos)) {
                    $validator->errors()->add("detalles.{$index}", 'Documento duplicado en la lista.');
                }
                
                $documentos[] = $key;
            }
            
            // Validar plazo de 7 días para comunicación de baja
            $fechaReferencia = $this->input('fecha_referencia');
            if ($fechaReferencia) {
                $fechaRef = \Carbon\Carbon::parse($fechaReferencia);
                $hoy = \Carbon\Carbon::now();
                
                if ($fechaRef->diffInDays($hoy) > 7) {
                    $validator->errors()->add('fecha_referencia', 'Solo se pueden anular documentos emitidos en los últimos 7 días calendario.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'La empresa es requerida.',
            'company_id.exists' => 'La empresa seleccionada no existe.',
            'branch_id.required' => 'La sucursal es requerida.',
            'branch_id.exists' => 'La sucursal seleccionada no existe.',
            'fecha_referencia.required' => 'La fecha de referencia es requerida.',
            'fecha_referencia.date' => 'La fecha de referencia debe ser una fecha válida.',
            'fecha_referencia.before_or_equal' => 'La fecha de referencia no puede ser mayor a hoy.',
            'fecha_referencia.after_or_equal' => 'Solo se pueden anular documentos de los últimos 7 días.',
            'motivo_baja.required' => 'El motivo de baja es requerido.',
            'motivo_baja.max' => 'El motivo de baja no puede exceder 500 caracteres.',
            
            'detalles.required' => 'Se requiere al menos un documento para anular.',
            'detalles.array' => 'Los detalles deben ser un array.',
            'detalles.min' => 'Se requiere al menos un documento para anular.',
            'detalles.max' => 'No se pueden anular más de 100 documentos por comunicación.',
            
            'detalles.*.tipo_documento.required' => 'El tipo de documento es requerido.',
            'detalles.*.tipo_documento.in' => 'Tipo de documento inválido. Valores permitidos: 01, 03, 07, 08, 09.',
            'detalles.*.serie.required' => 'La serie es requerida.',
            'detalles.*.serie.max' => 'La serie no puede exceder 4 caracteres.',
            'detalles.*.correlativo.required' => 'El correlativo es requerido.',
            'detalles.*.correlativo.max' => 'El correlativo no puede exceder 8 caracteres.',
            'detalles.*.motivo_especifico.required' => 'El motivo específico es requerido.',
            'detalles.*.motivo_especifico.max' => 'El motivo específico no puede exceder 250 caracteres.',
        ];
    }
}