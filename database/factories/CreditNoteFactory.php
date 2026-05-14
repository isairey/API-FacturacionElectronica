<?php

namespace Database\Factories;

use App\Models\CreditNote;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CreditNote>
 */
class CreditNoteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CreditNote::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'client_id' => Client::factory(),
            'tipo_documento' => '07',
            'serie' => 'FC01',
            'correlativo' => str_pad((string) fake()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'tipo_doc_afectado' => '01',
            'num_doc_afectado' => 'F001-000001',
            'cod_motivo' => '07',
            'des_motivo' => 'DEVOLUCION POR ITEM',
            'fecha_emision' => now()->format('Y-m-d'),
            'ubl_version' => '2.1',
            'moneda' => 'PEN',
            'forma_pago_tipo' => 'Contado',
            'forma_pago_cuotas' => null,
            'valor_venta' => 100.00,
            'mto_oper_gravadas' => 100.00,
            'mto_oper_exoneradas' => 0.00,
            'mto_oper_inafectas' => 0.00,
            'mto_oper_gratuitas' => 0.00,
            'mto_igv' => 18.00,
            'mto_base_ivap' => 0.00,
            'mto_ivap' => 0.00,
            'mto_isc' => 0.00,
            'mto_icbper' => 0.00,
            'total_impuestos' => 18.00,
            'mto_imp_venta' => 118.00,
            'detalles' => [
                [
                    'codigo' => 'PROD001',
                    'descripcion' => 'Producto de prueba',
                    'unidad' => 'NIU',
                    'cantidad' => 2,
                    'mto_valor_unitario' => 50.00,
                    'porcentaje_igv' => 18.00,
                    'tip_afe_igv' => '10',
                ]
            ],
            'leyendas' => [
                [
                    'code' => '1000',
                    'value' => 'CIENTO DIECIOCHO CON 00/100 SOLES'
                ]
            ],
            'guias' => null,
            'datos_adicionales' => null,
            'xml_path' => null,
            'cdr_path' => null,
            'pdf_path' => null,
            'estado_sunat' => 'PENDIENTE',
            'respuesta_sunat' => null,
            'codigo_hash' => null,
            'usuario_creacion' => 'SYSTEM',
        ];
    }

    /**
     * Create a credit note with payment in installments.
     */
    public function withInstallments(): static
    {
        return $this->state(fn (array $attributes) => [
            'forma_pago_tipo' => 'Credito',
            'forma_pago_cuotas' => [
                [
                    'monto' => 118.00,
                    'fecha_pago' => now()->addDays(30)->format('Y-m-d')
                ]
            ],
        ]);
    }

    /**
     * Create a credit note for a boleta.
     */
    public function forBoleta(): static
    {
        return $this->state(fn (array $attributes) => [
            'serie' => 'BC01',
            'tipo_doc_afectado' => '03',
            'num_doc_afectado' => 'B001-000001',
        ]);
    }

    /**
     * Create a credit note with guides.
     */
    public function withGuides(): static
    {
        return $this->state(fn (array $attributes) => [
            'guias' => [
                [
                    'tipo_doc' => '09',
                    'nro_doc' => '0001-213'
                ]
            ],
        ]);
    }

    /**
     * Create a credit note with SUNAT accepted status.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado_sunat' => 'ACEPTADO',
        ]);
    }
}