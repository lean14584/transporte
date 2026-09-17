<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Guia;
use App\Models\Transporte;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guia>
 */
class GuiaFactory extends Factory
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
            'cliente_id' => Cliente::factory(),
            'transportista' => fake()->name(),
            'camion_marca' => fake()->randomElement(['Scania', 'Mercedes-Benz', 'Iveco', 'Volvo']),
            'camion_modelo' => fake()->bothify('R###'),
            'camion_patente' => strtoupper(fake()->bothify('AA###??')),
            'camion_color' => fake()->safeColorName(),
            'mercaderia' => fake()->randomElement(['Cereal', 'Fertilizante', 'Madera', 'Chapa']),
            'cantidad' => fake()->randomFloat(2, 500, 30000),
            'unidad' => fake()->randomElement(['kilos', 'unidades']),
            'fecha_carga' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'estado' => 'cargada',
        ];
    }

    public function recibida(): static
    {
        return $this->state(function () {
            $bruto = fake()->randomFloat(2, 10000, 40000);
            $tara = fake()->randomFloat(2, 5000, 9000);

            return [
                'estado' => 'recibida',
                'pesaje_bruto' => $bruto,
                'tara' => $tara,
                'neto' => $bruto - $tara,
                'porcentaje_ganancia' => 10,
                'monto_ganancia' => round(($bruto - $tara) * 0.10, 2),
                'fecha_recepcion' => now()->toDateString(),
            ];
        });
    }
}
