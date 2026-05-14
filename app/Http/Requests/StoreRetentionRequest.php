<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRetentionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Empresa y sucursal
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'serie' => 'required|string|max:4',
            'correlativo' => 'required|string|max:8',
            'fecha_emision' => 'required|date',
            'moneda' => 'required|string|in:PEN,USD',
            
            // Información de retención
            'regimen' => 'required|string|in:01,02,03',
            'tasa' => 'required|numeric|min:0|max:100',
            'observacion' => 'nullable|string|max:500',
            'imp_retenido' => 'required|numeric|min:0',
            'imp_pagado' => 'required|numeric|min:0',
            
            // Proveedor
            'proveedor.tipo_documento' => 'required|string|in:1,4,6,0',
            'proveedor.numero_documento' => 'required|string|max:15',
            'proveedor.razon_social' => 'required|string|max:255',
            'proveedor.nombre_comercial' => 'nullable|string|max:255',
            'proveedor.direccion' => 'nullable|string|max:255',
            'proveedor.ubigeo' => 'nullable|string|size:6',
            'proveedor.distrito' => 'nullable|string|max:100',
            'proveedor.provincia' => 'nullable|string|max:100',
            'proveedor.departamento' => 'nullable|string|max:100',
            'proveedor.telefono' => 'nullable|string|max:20',
            'proveedor.email' => 'nullable|email|max:100',
            
            // Detalles de retención
            'detalles' => 'required|array|min:1',
            'detalles.*.tipo_doc' => 'required|string|in:01,03,12,14',
            'detalles.*.num_doc' => 'required|string|max:20',
            'detalles.*.fecha_emision' => 'required|date',
            'detalles.*.fecha_retencion' => 'required|date|after_or_equal:detalles.*.fecha_emision',
            'detalles.*.moneda' => 'required|string|in:PEN,USD',
            'detalles.*.imp_total' => 'required|numeric|min:0',
            'detalles.*.imp_pagar' => 'required|numeric|min:0',
            'detalles.*.imp_retenido' => 'required|numeric|min:0',
            
            // Pagos dentro de cada detalle
            'detalles.*.pagos' => 'required|array|min:1',
            'detalles.*.pagos.*.moneda' => 'required|string|in:PEN,USD',
            'detalles.*.pagos.*.fecha' => 'required|date',
            'detalles.*.pagos.*.importe' => 'required|numeric|min:0',
            
            // Tipo de cambio dentro de cada detalle
            'detalles.*.tipo_cambio' => 'required|array',
            'detalles.*.tipo_cambio.fecha' => 'required|date',
            'detalles.*.tipo_cambio.factor' => 'required|numeric|min:0',
            'detalles.*.tipo_cambio.moneda_obj' => 'required|string|in:PEN,USD',
            'detalles.*.tipo_cambio.moneda_ref' => 'required|string|in:PEN,USD',
        ];
    }

    public function messages(): array
    {
        return [
            // Empresa y sucursal
            'company_id.required' => 'La empresa es requerida.',
            'company_id.exists' => 'La empresa no existe.',
            'branch_id.required' => 'La sucursal es requerida.',
            'branch_id.exists' => 'La sucursal no existe.',
            'serie.required' => 'La serie es requerida.',
            'correlativo.required' => 'El correlativo es requerido.',
            'fecha_emision.required' => 'La fecha de emisión es requerida.',
            'moneda.required' => 'La moneda es requerida.',
            
            // Información de retención
            'regimen.required' => 'El régimen de retención es requerido.',
            'regimen.in' => 'El régimen debe ser 01, 02 o 03.',
            'tasa.required' => 'La tasa de retención es requerida.',
            'imp_retenido.required' => 'El importe retenido es requerido.',
            'imp_pagado.required' => 'El importe pagado es requerido.',
            
            // Proveedor
            'proveedor.tipo_documento.required' => 'El tipo de documento del proveedor es requerido.',
            'proveedor.numero_documento.required' => 'El número de documento del proveedor es requerido.',
            'proveedor.razon_social.required' => 'La razón social del proveedor es requerida.',
            
            // Detalles
            'detalles.required' => 'Los detalles son requeridos.',
            'detalles.*.tipo_doc.required' => 'El tipo de documento es requerido.',
            'detalles.*.num_doc.required' => 'El número de documento es requerido.',
            'detalles.*.fecha_emision.required' => 'La fecha de emisión es requerida.',
            'detalles.*.fecha_retencion.required' => 'La fecha de retención es requerida.',
            'detalles.*.imp_total.required' => 'El importe total es requerido.',
            'detalles.*.imp_pagar.required' => 'El importe a pagar es requerido.',
            'detalles.*.imp_retenido.required' => 'El importe retenido es requerido.',
            
            // Pagos
            'detalles.*.pagos.required' => 'Los pagos son requeridos.',
            'detalles.*.pagos.*.moneda.required' => 'La moneda del pago es requerida.',
            'detalles.*.pagos.*.fecha.required' => 'La fecha del pago es requerida.',
            'detalles.*.pagos.*.importe.required' => 'El importe del pago es requerido.',
            
            // Tipo de cambio
            'detalles.*.tipo_cambio.required' => 'El tipo de cambio es requerido.',
            'detalles.*.tipo_cambio.fecha.required' => 'La fecha del tipo de cambio es requerida.',
            'detalles.*.tipo_cambio.factor.required' => 'El factor del tipo de cambio es requerido.',
        ];
    }

    protected function prepareForValidation()
    {
        // Validaciones adicionales antes del proceso principal
        $this->merge([
            'branch_id' => $this->branch_id ?? null,
        ]);

        // Validar que la sucursal pertenezca a la empresa
        if ($this->company_id && $this->branch_id) {
            $branch = \App\Models\Branch::find($this->branch_id);
            if ($branch && $branch->company_id != $this->company_id) {
                $this->merge(['branch_id' => null]);
            }
        }
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que la sucursal pertenezca a la empresa seleccionada
            if ($this->company_id && $this->branch_id) {
                $branch = \App\Models\Branch::find($this->branch_id);
                if (!$branch || $branch->company_id != $this->company_id) {
                    $validator->errors()->add('branch_id', 'La sucursal no pertenece a la empresa seleccionada.');
                }
            }
        });
    }
}