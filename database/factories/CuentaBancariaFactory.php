<?php

namespace Database\Factories;

use App\Models\CuentaBancaria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CuentaBancaria>
 */
class CuentaBancariaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'banco' => fake()->randomElement(['Banco Nación', 'Banco Provincia', 'Banco Galicia', 'Banco Santander']),
            'numero_cuenta' => fake()->numerify('####-####-##'),
            'alias_cbu' => fake()->numerify('####################'),
            'saldo' => fake()->randomFloat(2, 0, 1000000),
        ];
    }
}
