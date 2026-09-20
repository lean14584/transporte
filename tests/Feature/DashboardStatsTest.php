<?php

use App\Models\Cheque;
use App\Models\Cliente;
use App\Models\CuentaBancaria;
use App\Models\Guia;
use App\Models\Recibo;
use App\Models\Transporte;
use App\Models\User;
use Livewire\Livewire;

test('el dashboard muestra los indicadores calculados a partir de los datos reales', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create();
    $transporte = Transporte::factory()->create();

    Guia::factory()->for($cliente)->for($transporte)->create(); // en tránsito
    Guia::factory()->for($cliente)->for($transporte)->create()->recepcionar(10000, 2000, 8000, 0, 0, 800); // +800 cta cte

    Recibo::cobrar($cliente->refresh(), 300, 'efectivo');

    $cuenta = CuentaBancaria::factory()->create(['saldo' => 50000]);
    Cheque::emitir($cuenta, [
        'numero' => '1',
        'banco' => 'Banco Nación',
        'beneficiario' => $transporte->nombre,
        'fecha_pago' => now()->addDays(10)->toDateString(),
        'monto' => 5000,
    ]);

    $component = Livewire::actingAs($user)->test('pages::dashboard');

    expect($component->instance()->guiasEnTransito)->toBe(1)
        ->and($component->instance()->cobradoHoy)->toEqual(300.0)
        ->and($component->instance()->totalACobrar)->toEqual(500.0)
        ->and($component->instance()->saldoBancario)->toEqual(45000.0);

    $component->assertSee($cliente->nombre)
        ->assertSee($transporte->nombre);
});
