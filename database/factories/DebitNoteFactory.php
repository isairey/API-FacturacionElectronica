<?php

namespace Database\Factories;

use App\Models\DebitNote;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DebitNote>
 */
class DebitNoteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DebitNote::class;

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
            'tipo_documento' => '08',
            'serie' => 'FD01',
            'correlativo' => str_pad((string) fake()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'tipo_doc_afectado' => '01',
            'num_doc_afectado' => 'F001-000001',
            'cod_motivo' => '02',
            'des_motivo' => 'AUMENTO EN EL VALOR',
            'fecha_emision' => now()->format('Y-m-d'),
            'ubl_version' => '2.1',
            'moneda' => 'PEN',
            'valor_venta' => 100.00,
            'mto_oper_gravadas' => 100.00,
            'mto_oper_exoneradas' => 0.00,
            'mto_oper_inafectas' => 0.00,
            'mto_igv' => 18.00,
            'mto_isc' => 0.00,
            'total_impuestos' => 18.00,
            'mto_imp_venta' => 118.00,
            'detalles' => [
                [
                    'codigo' => 'PROD001',
                    'descripcion' => 'Aumento por concepto adicional',
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
     * Create a debit note for a boleta.
     */
    public function forBoleta(): static
    {
        return $this->state(fn (array $attributes) => [
            'serie' => 'BD01',
            'tipo_doc_afectado' => '03',
            'num_doc_afectado' => 'B001-000001',
        ]);
    }

    /**
     * Create a debit note with interest motive.
     */
    public function withInterest(): static
    {
        return $this->state(fn (array $attributes) => [
            'cod_motivo' => '01',
            'des_motivo' => 'INTERESES POR MORA',
        ]);
    }

    /**
     * Create a debit note with penalties motive.
     */
    public function withPenalties(): static
    {
        return $this->state(fn (array $attributes) => [
            'cod_motivo' => '03',
            'des_motivo' => 'PENALIDADES/OTROS CONCEPTOS',
        ]);
    }

    /**
     * Create a debit note with SUNAT accepted status.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado_sunat' => 'ACEPTADO',
        ]);
    }
}