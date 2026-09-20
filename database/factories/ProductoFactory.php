<?php

namespace Database\Factories;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero_producto' => fake()->unique()->numerify('PRO-####'),
            'nombre' => fake()->words(2, true),
            'forma_de_carga' => fake()->randomElement(['Granel', 'Bolsas', 'Pallets', 'Contenedor']),
        ];
    }
}
