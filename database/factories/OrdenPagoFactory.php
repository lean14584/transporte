<?php

namespace Database\Factories;

use App\Models\OrdenPago;
use App\Models\Transporte;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrdenPago>
 */
class OrdenPagoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transporte_id' => Transporte::factory(),
            'fecha' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'monto' => fake()->randomFloat(2, 1000, 100000),
            'concepto' => 'Pago de flete',
            'medio_pago' => fake()->randomElement(['efectivo', 'transferencia', 'cheque']),
        ];
    }
}
