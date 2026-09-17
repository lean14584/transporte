<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'direccion' => fake()->streetAddress(),
            'telefono' => fake()->phoneNumber(),
            'dni' => fake()->numerify('########'),
            'condicion_iva' => fake()->randomElement([
                'responsable_inscripto',
                'monotributista',
                'exento',
                'consumidor_final',
            ]),
            'saldo_cta_cte' => 0,
        ];
    }
}
