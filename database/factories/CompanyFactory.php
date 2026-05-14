<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Company::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ruc' => '20' . fake()->unique()->numerify('#########'),
            'razon_social' => fake()->company(),
            'nombre_comercial' => 'EMPRESA PRUEBA',
            'direccion' => 'AV. EJEMPLO 123',
            'ubigeo' => '150101',
            'distrito' => 'LIMA',
            'provincia' => 'LIMA',
            'departamento' => 'LIMA',
            'telefono' => '01-1234567',
            'email' => 'test@empresa.com',
            'web' => 'https://empresa.com',
            'usuario_sol' => 'TESTUSER',
            'clave_sol' => 'TESTPASS',
            'certificado_pem' => '-----BEGIN CERTIFICATE-----
MIICertificateDataForTesting
-----END CERTIFICATE-----',
            'certificado_password' => 'TESTCERTPASS',
            'endpoint_beta' => 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService',
            'endpoint_produccion' => 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService',
            'modo_produccion' => false,
            'logo_path' => null,
            'configuraciones' => null,
            'activo' => true,
        ];
    }

    /**
     * Indicate that the company is in production mode.
     */
    public function production(): static
    {
        return $this->state(fn (array $attributes) => [
            'modo_produccion' => true,
        ]);
    }

    /**
     * Indicate that the company is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }
}