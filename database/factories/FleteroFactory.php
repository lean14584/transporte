<?php

namespace Database\Factories;

use App\Models\Fletero;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fletero>
 */
class FleteroFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero_fletero' => fake()->unique()->numerify('FLE-####'),
            'razon_social' => fake()->company(),
            'direccion' => fake()->streetAddress(),
            'cuit' => fake()->numerify('##-########-#'),
            'dni' => fake()->numerify('########'),
            'telefono' => fake()->phoneNumber(),
            'contacto' => fake()->name(),
            'nacionalidad' => 'Argentina',
            'saldo_cta_cte' => 0,
        ];
    }
}
