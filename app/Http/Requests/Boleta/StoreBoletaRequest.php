<?php

namespace App\Http\Requests\Boleta;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;

class StoreBoletaRequest extends FormRequest
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
            'tipo_operacion' => 'nullable|string|max:4',
            'moneda' => 'nullable|string|max:3',
            'metodo_envio' => 'required|string|in:individual,resumen_diario',
            'forma_pago_tipo' => 'nullable|string|max:20',
            'forma_pago_cuotas' => 'nullable|array',
            
            // Cliente
            'client' => 'required|array',
            'client.tipo_documento' => 'required|string|max:1',
            'client.numero_documento' => 'required|string|max:15',
            'client.razon_social' => 'required|string|max:255',
            'client.nombre_comercial' => 'nullable|string|max:255',
            'client.direccion' => 'nullable|string|max:255',
            'client.ubigeo' => 'nullable|string|max:6',
            'client.distrito' => 'nullable|string|max:100',
            'client.provincia' => 'nullable|string|max:100',
            'client.departamento' => 'nullable|string|max:100',
            'client.telefono' => 'nullable|string|max:20',
            'client.email' => 'nullable|email|max:255',
            
            // Detalles
            'detalles' => 'required|array|min:1',
            'detalles.*.codigo' => 'required|string|max:30',
            'detalles.*.descripcion' => 'required|string|max:255',
            'detalles.*.unidad' => 'required|string|max:3',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.mto_valor_unitario' => 'required|numeric|min:0',
            'detalles.*.mto_valor_gratuito' => 'nullable|numeric|min:0',
            'detalles.*.porcentaje_igv' => 'required|numeric|min:0|max:100',
            'detalles.*.porcentaje_ivap' => 'nullable|numeric|min:0|max:100',
            'detalles.*.tip_afe_igv' => 'required|string|max:2',
            'detalles.*.isc' => 'nullable|numeric|min:0',
            'detalles.*.icbper' => 'nullable|numeric|min:0',
            'detalles.*.factor_icbper' => 'nullable|numeric|min:0',
            
            // Leyendas
            'leyendas' => 'nullable|array',
            'leyendas.*.code' => 'required|string|max:4',
            'leyendas.*.value' => 'required|string|max:255',
            
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
            'metodo_envio.required' => 'El método de envío es requerido.',
            'metodo_envio.in' => 'El método de envío debe ser individual o resumen_diario.',
            
            'client.required' => 'Los datos del cliente son requeridos.',
            'client.tipo_documento.required' => 'El tipo de documento del cliente es requerido.',
            'client.numero_documento.required' => 'El número de documento del cliente es requerido.',
            'client.razon_social.required' => 'La razón social del cliente es requerida.',
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
            'detalles.*.porcentaje_igv.required' => 'El porcentaje de IGV es requerido.',
            'detalles.*.tip_afe_igv.required' => 'El tipo de afectación IGV es requerido.',
        ];
    }
}
