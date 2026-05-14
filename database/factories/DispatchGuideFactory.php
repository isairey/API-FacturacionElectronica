<?php

namespace Database\Factories;

use App\Models\DispatchGuide;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DispatchGuide>
 */
class DispatchGuideFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DispatchGuide::class;

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
            'tipo_documento' => '09',
            'serie' => 'T001',
            'correlativo' => str_pad((string) fake()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'numero_completo' => null, // Se genera automáticamente en el modelo
            'fecha_emision' => now()->format('Y-m-d'),
            'fecha_traslado' => now()->addDays(1)->format('Y-m-d'),
            'version' => '2022',
            
            // Datos del envío
            'cod_traslado' => '01', // Venta
            'des_traslado' => 'Venta',
            'mod_traslado' => '02', // Transporte privado por defecto
            'peso_total' => 50.5,
            'und_peso_total' => 'KGM',
            'num_bultos' => 2,
            
            // Direcciones (JSON)
            'partida' => [
                'ubigeo' => '150101',
                'direccion' => 'AV LIMA 123, LIMA'
            ],
            'llegada' => [
                'ubigeo' => '150203', 
                'direccion' => 'AV ARRIOLA 456, SAN MARTIN DE PORRES'
            ],
            
            // Transportista y vehículo (JSON) - para transporte privado por defecto
            'transportista' => null,
            'vehiculo' => [
                'placa_principal' => 'ABC123',
                'placa_secundaria' => null,
                'autorizacion' => null
            ]
            
            // Detalles de productos
            'detalles' => [
                [
                    'cantidad' => 10,
                    'unidad' => 'NIU',
                    'descripcion' => 'PRODUCTO ELECTRÓNICO',
                    'codigo' => 'PROD001',
                    'peso_total' => 25.0
                ],
                [
                    'cantidad' => 5,
                    'unidad' => 'NIU', 
                    'descripcion' => 'ACCESORIO TECNOLÓGICO',
                    'codigo' => 'PROD002',
                    'peso_total' => 25.5
                ]
            ],
            
            'documentos_relacionados' => null,
            'datos_adicionales' => null,
            
            // Archivos generados
            'xml_path' => null,
            'cdr_path' => null,
            'pdf_path' => null,
            
            // Estado SUNAT
            'estado_sunat' => 'PENDIENTE',
            'respuesta_sunat' => null,
            'ticket' => null,
            'codigo_hash' => null,
            
            // Auditoría
            'usuario_creacion' => 'SYSTEM',
        ];
    }

    /**
     * Create a dispatch guide for public transport.
     */
    public function publicTransport(): static
    {
        return $this->state(fn (array $attributes) => [
            'mod_traslado' => '01',
            'transportista' => [
                'tipo_doc' => '6',
                'num_doc' => '20123456789',
                'razon_social' => 'TRANSPORTES PUBLICOS SAC',
                'nro_mtc' => 'MTC001'
            ],
            'vehiculo' => [
                'placa_principal' => 'TPU123',
                'placa_secundaria' => null,
                'autorizacion' => null
            ]
        ]);
    }

    /**
     * Create a dispatch guide for same company transfer.
     */
    public function sameCompanyTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'cod_traslado' => '04',
            'des_traslado' => 'Traslado entre establecimientos de la misma empresa',
        ]);
    }

    /**
     * Create a dispatch guide for purchase.
     */
    public function purchase(): static
    {
        return $this->state(fn (array $attributes) => [
            'cod_traslado' => '02',
            'des_traslado' => 'Compra',
        ]);
    }

    /**
     * Create a dispatch guide for return.
     */
    public function return(): static
    {
        return $this->state(fn (array $attributes) => [
            'cod_traslado' => '06',
            'des_traslado' => 'Devolución',
        ]);
    }

    /**
     * Create a dispatch guide with multiple secondary vehicles.
     */
    public function withSecondaryVehicles(): static
    {
        return $this->state(fn (array $attributes) => [
            'vehiculo' => [
                'placa_principal' => 'VEH001',
                'placa_secundaria' => ['DEF456', 'GHI789'],
                'autorizacion' => null
            ],
        ]);
    }

    /**
     * Create a dispatch guide with SUNAT accepted status.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado_sunat' => 'ACEPTADO',
        ]);
    }

    /**
     * Create a dispatch guide with processing status and ticket.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado_sunat' => 'PROCESANDO',
            'ticket' => fake()->regexify('[A-Z0-9]{15}'),
        ]);
    }
}