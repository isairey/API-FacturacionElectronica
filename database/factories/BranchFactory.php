<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Branch>
 */
class BranchFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Branch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'codigo' => '0000',
            'nombre' => 'Sucursal Principal',
            'direccion' => 'AV. EJEMPLO 123',
            'ubigeo' => '150101',
            'distrito' => 'LIMA',
            'provincia' => 'LIMA',
            'departamento' => 'LIMA',
            'telefono' => '01-1234567',
            'email' => 'sucursal@empresa.com',
            'series_factura' => ['FF01', 'FF02'],
            'series_boleta' => ['BB01', 'BB02'],
            'series_nota_credito' => ['FC01', 'FC02'],
            'series_nota_debito' => ['FD01', 'FD02'],
            'series_guia_remision' => ['T001', 'T002'],
            'activo' => true,
        ];
    }

    /**
     * Indicate that the branch is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }
}