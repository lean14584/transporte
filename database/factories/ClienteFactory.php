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
            'numero_cliente' => fake()->unique()->numerify('CLI-####'),
            'nombre' => fake()->name(),
            'razon_social' => fake()->company(),
            'direccion' => fake()->streetAddress(),
            'telefono' => fake()->phoneNumber(),
            'dni' => fake()->numerify('########'),
            'cuit' => fake()->numerify('##-########-#'),
            'contacto' => fake()->name(),
            'nacionalidad' => 'Argentina',
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
