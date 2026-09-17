<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Recibo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recibo>
 */
class ReciboFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'fecha' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'monto' => fake()->randomFloat(2, 1000, 50000),
            'concepto' => 'Cobranza',
            'medio_pago' => fake()->randomElement(['efectivo', 'transferencia', 'cheque']),
        ];
    }
}
