<?php

namespace Database\Factories;

use App\Models\Cheque;
use App\Models\CuentaBancaria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cheque>
 */
class ChequeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cuenta_bancaria_id' => CuentaBancaria::factory(),
            'numero' => fake()->numerify('########'),
            'banco' => fake()->randomElement(['Banco Nación', 'Banco Provincia', 'Banco Galicia']),
            'beneficiario' => fake()->company(),
            'fecha_emision' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'fecha_pago' => fake()->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            'monto' => fake()->randomFloat(2, 1000, 100000),
            'estado' => 'emitido',
        ];
    }
}
