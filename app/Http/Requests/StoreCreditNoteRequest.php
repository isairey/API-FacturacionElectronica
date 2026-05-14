<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Branch;

class StoreCreditNoteRequest extends FormRequest
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
            'serie' => 'required|string|max:4',
            'fecha_emision' => 'required|date',
            'ubl_version' => 'nullable|string|max:5',
            'moneda' => 'required|string|in:PEN,USD',
            
            // Documento afectado
            'tipo_doc_afectado' => 'required|string|in:01,03,07,08',
            'num_doc_afectado' => 'required|string|max:20',
            'cod_motivo' => 'required|string|in:01,02,03,04,05,06,07,08,09,10,11,12,13',
            'des_motivo' => 'required|string|max:250',
            
            // Forma de pago (opcional)
            'forma_pago_tipo' => 'nullable|string|in:Contado,Credito',
            'forma_pago_cuotas' => 'nullable|array',
            'forma_pago_cuotas.*.monto' => 'required_with:forma_pago_cuotas|numeric|min:0.01',
            'forma_pago_cuotas.*.fecha_pago' => 'required_with:forma_pago_cuotas|date|after:fecha_emision',
            
            // Cliente
            'client.tipo_documento' => 'required|string|in:1,4,6,0',
            'client.numero_documento' => 'required|string|max:15',
            'client.razon_social' => 'required|string|max:255',
            'client.nombre_comercial' => 'nullable|string|max:255',
            'client.direccion' => 'nullable|string|max:255',
            'client.ubigeo' => 'nullable|string|size:6',
            'client.distrito' => 'nullable|string|max:100',
            'client.provincia' => 'nullable|string|max:100',
            'client.departamento' => 'nullable|string|max:100',
            'client.telefono' => 'nullable|string|max:20',
            'client.email' => 'nullable|email|max:100',

            // Detalles
            'detalles' => 'required|array|min:1',
            'detalles.*.codigo' => 'required|string|max:50',
            'detalles.*.descripcion' => 'required|string|max:500',
            'detalles.*.unidad' => 'required|string|max:3',
            'detalles.*.cantidad' => 'required|numeric|min:0.001',
            'detalles.*.mto_valor_unitario' => 'required|numeric|min:0',
            'detalles.*.porcentaje_igv' => 'nullable|numeric|min:0',
            'detalles.*.tip_afe_igv' => 'nullable|string|in:10,11,12,13,14,15,16,17,20,21,30,31,32,33,34,35,36,40',
            'detalles.*.codigo_producto_sunat' => 'nullable|string|max:50',

            // Guías (opcional)
            'guias' => 'nullable|array',
            'guias.*.tipo_doc' => 'required_with:guias|string|max:2',
            'guias.*.nro_doc' => 'required_with:guias|string|max:20',

            // Leyendas
            'leyendas' => 'nullable|array',
            'leyendas.*.code' => 'required_with:leyendas|string|max:4',
            'leyendas.*.value' => 'required_with:leyendas|string|max:500',

            'datos_adicionales' => 'nullable|array',
            'usuario_creacion' => 'nullable|string|max:100',
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
        });
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'La empresa es requerida.',
            'company_id.exists' => 'La empresa seleccionada no existe.',
            'branch_id.required' => 'La sucursal es requerida.',
            'branch_id.exists' => 'La sucursal seleccionada no existe.',
            'serie.required' => 'La serie es requerida.',
            'serie.max' => 'La serie no puede tener más de 4 caracteres.',
            'fecha_emision.required' => 'La fecha de emisión es requerida.',
            'fecha_emision.date' => 'La fecha de emisión debe ser una fecha válida.',
            'moneda.required' => 'La moneda es requerida.',
            'moneda.in' => 'La moneda debe ser PEN o USD.',
            
            'tipo_doc_afectado.required' => 'El tipo de documento afectado es requerido.',
            'tipo_doc_afectado.in' => 'El tipo de documento afectado debe ser válido (01=Factura, 03=Boleta, 07=Nota Crédito, 08=Nota Débito).',
            'num_doc_afectado.required' => 'El número de documento afectado es requerido.',
            'cod_motivo.required' => 'El código de motivo es requerido.',
            'des_motivo.required' => 'La descripción del motivo es requerida.',
            
            'client.tipo_documento.required' => 'El tipo de documento del cliente es requerido.',
            'client.tipo_documento.in' => 'El tipo de documento del cliente debe ser válido.',
            'client.numero_documento.required' => 'El número de documento del cliente es requerido.',
            'client.razon_social.required' => 'La razón social del cliente es requerida.',
            'client.ubigeo.size' => 'El ubigeo debe tener exactamente 6 caracteres.',
            'client.email.email' => 'El email del cliente debe ser válido.',
            
            'detalles.required' => 'Los detalles son requeridos.',
            'detalles.min' => 'Debe incluir al menos un detalle.',
            'detalles.*.codigo.required' => 'El código del producto es requerido.',
            'detalles.*.descripcion.required' => 'La descripción del producto es requerida.',
            'detalles.*.unidad.required' => 'La unidad del producto es requerida.',
            'detalles.*.cantidad.required' => 'La cantidad del producto es requerida.',
            'detalles.*.cantidad.min' => 'La cantidad debe ser mayor a 0.',
            'detalles.*.mto_valor_unitario.required' => 'El valor unitario es requerido.',
            'detalles.*.mto_valor_unitario.min' => 'El valor unitario debe ser mayor o igual a 0.',
            
            'forma_pago_tipo.in' => 'La forma de pago debe ser Contado o Credito.',
            'forma_pago_cuotas.*.monto.required_with' => 'El monto de la cuota es requerido.',
            'forma_pago_cuotas.*.monto.min' => 'El monto de la cuota debe ser mayor a 0.',
            'forma_pago_cuotas.*.fecha_pago.required_with' => 'La fecha de pago de la cuota es requerida.',
            'forma_pago_cuotas.*.fecha_pago.after' => 'La fecha de pago debe ser posterior a la fecha de emisión.',
            
            'guias.*.tipo_doc.required_with' => 'El tipo de documento de la guía es requerido cuando se especifica una guía.',
            'guias.*.nro_doc.required_with' => 'El número de documento de la guía es requerido cuando se especifica una guía.',
            'leyendas.*.code.required_with' => 'El código de la leyenda es requerido cuando se especifica una leyenda.',
            'leyendas.*.value.required_with' => 'El valor de la leyenda es requerido cuando se especifica una leyenda.',
        ];
    }
}