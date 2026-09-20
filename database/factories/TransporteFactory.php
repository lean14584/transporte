<?php

namespace Database\Factories;

use App\Models\Transporte;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transporte>
 */
class TransporteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero_transporte' => fake()->unique()->numerify('TRA-####'),
            'nombre' => fake()->company(),
            'direccion' => fake()->streetAddress(),
            'telefono' => fake()->phoneNumber(),
            'dueno' => fake()->name(),
            'cuit' => fake()->numerify('##-########-#'),
            'nacionalidad' => 'Argentina',
        ];
    }
}
