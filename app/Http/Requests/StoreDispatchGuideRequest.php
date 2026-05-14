<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Branch;

class StoreDispatchGuideRequest extends FormRequest
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
            'destinatario_id' => 'required|exists:clients,id',
            'serie' => 'required|string|max:4',
            'fecha_emision' => 'required|date',
            'version' => 'nullable|string|max:10',
            
            // Datos del envío
            'cod_traslado' => 'required|string|max:2',
            'des_traslado' => 'nullable|string|max:250',
            'mod_traslado' => 'required|string|in:01,02',
            'fecha_traslado' => 'required|date|after_or_equal:fecha_emision',
            'peso_total' => 'required|numeric|min:0.001',
            'und_peso_total' => 'required|string|max:3',
            'num_bultos' => 'required|integer|min:1',
            
            // Direcciones - Soporte para formato nested y plano
            'partida' => 'nullable|array',
            'partida.ubigeo' => 'required_with:partida|string|size:6',
            'partida.direccion' => 'required_with:partida|string|max:255',
            'partida.ruc' => 'nullable|string|max:11',
            'partida.cod_local' => 'nullable|string|max:10',
            
            'llegada' => 'nullable|array', 
            'llegada.ubigeo' => 'required_with:llegada|string|size:6',
            'llegada.direccion' => 'required_with:llegada|string|max:255',
            'llegada.ruc' => 'nullable|string|max:11',
            'llegada.cod_local' => 'nullable|string|max:10',
            
            // Formato plano (legacy)
            'partida_ubigeo' => 'required_without:partida|string|size:6',
            'partida_direccion' => 'required_without:partida|string|max:255',
            'partida_ruc' => 'nullable|string|max:11',
            'partida_cod_local' => 'nullable|string|max:10',
            
            'llegada_ubigeo' => 'required_without:llegada|string|size:6',
            'llegada_direccion' => 'required_without:llegada|string|max:255',
            'llegada_ruc' => 'nullable|string|max:11',
            'llegada_cod_local' => 'nullable|string|max:10',
            
            // Transportista (si es transporte público)
            'transportista_tipo_doc' => 'nullable|string|max:1',
            'transportista_num_doc' => 'nullable|string|max:15',
            'transportista_razon_social' => 'nullable|string|max:255',
            'transportista_nro_mtc' => 'nullable|string|max:15',
            
            // Conductor (si es transporte privado)
            'conductor_tipo' => 'nullable|string|in:Principal,Secundario',
            'conductor_tipo_doc' => 'nullable|string|max:1',
            'conductor_num_doc' => 'nullable|string|max:15',
            'conductor_licencia' => 'nullable|string|max:15',
            'conductor_nombres' => 'nullable|string|max:100',
            'conductor_apellidos' => 'nullable|string|max:100',
            
            // Vehículo principal  
            'vehiculo_placa' => 'nullable|string|max:10',
            
            // Vehículos secundarios
            'vehiculos_secundarios' => 'nullable|array',
            'vehiculos_secundarios.*.placa' => 'required_with:vehiculos_secundarios|string|max:10',
            
            // Indicadores especiales (M1L, etc.)
            'indicadores' => 'nullable|array',
            'indicadores.*' => 'string|max:50',
            
            // Detalles de productos
            'detalles' => 'required|array|min:1',
            'detalles.*.cantidad' => 'required|numeric|min:0.001',
            'detalles.*.unidad' => 'required|string|max:3',
            'detalles.*.descripcion' => 'required|string|max:500',
            'detalles.*.codigo' => 'required|string|max:50',
            'detalles.*.peso_total' => 'nullable|numeric|min:0',
            
            // Observaciones
            'observaciones' => 'nullable|string|max:1000',
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

            // Validaciones específicas según modalidad de transporte
            if ($this->input('mod_traslado') === '01') { // Transporte público
                if (!$this->input('transportista_tipo_doc')) {
                    $validator->errors()->add('transportista_tipo_doc', 'El tipo de documento del transportista es requerido para transporte público.');
                }
                if (!$this->input('transportista_num_doc')) {
                    $validator->errors()->add('transportista_num_doc', 'El número de documento del transportista es requerido para transporte público.');
                }
                if (!$this->input('transportista_razon_social')) {
                    $validator->errors()->add('transportista_razon_social', 'La razón social del transportista es requerida para transporte público.');
                }
            } elseif ($this->input('mod_traslado') === '02') { // Transporte privado
                // Verificar si tiene indicador M1L
                $indicadores = $this->input('indicadores', []);
                $esM1L = is_array($indicadores) && in_array('SUNAT_Envio_IndicadorTrasladoVehiculoM1L', $indicadores);
                
                // Para M1L o traslado entre establecimientos (código 04), el conductor/vehículo es opcional
                if ($this->input('cod_traslado') !== '04' && !$esM1L) {
                    if (!$this->input('conductor_tipo_doc')) {
                        $validator->errors()->add('conductor_tipo_doc', 'El tipo de documento del conductor es requerido para transporte privado.');
                    }
                    if (!$this->input('conductor_num_doc')) {
                        $validator->errors()->add('conductor_num_doc', 'El número de documento del conductor es requerido para transporte privado.');
                    }
                    if (!$this->input('conductor_licencia')) {
                        $validator->errors()->add('conductor_licencia', 'La licencia del conductor es requerida para transporte privado.');
                    }
                    if (!$this->input('conductor_nombres')) {
                        $validator->errors()->add('conductor_nombres', 'Los nombres del conductor son requeridos para transporte privado.');
                    }
                    if (!$this->input('conductor_apellidos')) {
                        $validator->errors()->add('conductor_apellidos', 'Los apellidos del conductor son requeridos para transporte privado.');
                    }
                    if (!$this->input('vehiculo_placa')) {
                        $validator->errors()->add('vehiculo_placa', 'La placa del vehículo es requerida.');
                    }
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
            'destinatario_id.required' => 'El destinatario es requerido.',
            'destinatario_id.exists' => 'El destinatario seleccionado no existe.',
            'serie.required' => 'La serie es requerida.',
            'serie.max' => 'La serie no puede tener más de 4 caracteres.',
            'fecha_emision.required' => 'La fecha de emisión es requerida.',
            'fecha_emision.date' => 'La fecha de emisión debe ser una fecha válida.',
            
            'cod_traslado.required' => 'El código de motivo de traslado es requerido.',
            'mod_traslado.required' => 'La modalidad de traslado es requerida.',
            'mod_traslado.in' => 'La modalidad de traslado debe ser válida (01=Transporte público, 02=Transporte privado).',
            'fecha_traslado.required' => 'La fecha de traslado es requerida.',
            'fecha_traslado.after_or_equal' => 'La fecha de traslado debe ser igual o posterior a la fecha de emisión.',
            'peso_total.required' => 'El peso total es requerido.',
            'peso_total.min' => 'El peso total debe ser mayor a 0.',
            'und_peso_total.required' => 'La unidad de peso es requerida.',
            'num_bultos.required' => 'El número de bultos es requerido.',
            'num_bultos.min' => 'El número de bultos debe ser mayor a 0.',
            
            'partida_ubigeo.required' => 'El ubigeo de partida es requerido.',
            'partida_ubigeo.size' => 'El ubigeo de partida debe tener 6 caracteres.',
            'partida_direccion.required' => 'La dirección de partida es requerida.',
            'llegada_ubigeo.required' => 'El ubigeo de llegada es requerido.',
            'llegada_ubigeo.size' => 'El ubigeo de llegada debe tener 6 caracteres.',
            'llegada_direccion.required' => 'La dirección de llegada es requerida.',
            
            'vehiculo_placa.required' => 'La placa del vehículo es requerida.',
            'vehiculos_secundarios.*.placa.required_with' => 'La placa del vehículo secundario es requerida.',
            
            'detalles.required' => 'Los detalles son requeridos.',
            'detalles.min' => 'Debe incluir al menos un detalle.',
            'detalles.*.cantidad.required' => 'La cantidad es requerida.',
            'detalles.*.cantidad.min' => 'La cantidad debe ser mayor a 0.',
            'detalles.*.unidad.required' => 'La unidad es requerida.',
            'detalles.*.descripcion.required' => 'La descripción es requerida.',
            'detalles.*.codigo.required' => 'El código es requerido.',
            
            'conductor_tipo.in' => 'El tipo de conductor debe ser Principal o Secundario.',
            'observaciones.max' => 'Las observaciones no pueden exceder 1000 caracteres.',
        ];
    }
}