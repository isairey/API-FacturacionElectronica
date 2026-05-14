<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tipoDoc = fake()->randomElement(['1', '6']);
        
        return [
            'tipo_documento' => $tipoDoc,
            'numero_documento' => $tipoDoc === '1' 
                ? fake()->unique()->numerify('########') // DNI: 8 digits
                : '20' . fake()->unique()->numerify('#########'), // RUC: 20 + 9 digits
            'razon_social' => fake()->company(),
            'nombre_comercial' => fake()->optional()->company(),
            'direccion' => fake()->address(),
            'ubigeo' => '150101',
            'distrito' => 'LIMA',
            'provincia' => 'LIMA',
            'departamento' => 'LIMA',
            'telefono' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->companyEmail(),
            'activo' => true,
        ];
    }

    /**
     * Create a client with DNI document type.
     */
    public function withDni(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_documento' => '1',
            'numero_documento' => fake()->unique()->numerify('########'),
            'razon_social' => 'CLIENTE NATURAL',
            'nombre_comercial' => null,
        ]);
    }

    /**
     * Create a client with foreign ID document type.
     */
    public function withForeignId(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_documento' => '4',
            'numero_documento' => 'CE123456',
            'razon_social' => 'CLIENTE EXTRANJERO',
        ]);
    }

    /**
     * Indicate that the client is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }
}