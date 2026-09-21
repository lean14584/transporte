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
    $user = User::factory()->create(['role' => 'administrador']);
    $cliente = Cliente::factory()->create();
    $transporte = Transporte::factory()->create();

    Guia::factory()->for($cliente)->for($transporte)->create(); // en tránsito
    Guia::factory()->for($cliente)->for($transporte)->create()->recepcionar(totalDescargado: 8000, totalCliente: 800, totalFletero: 0, pesajeBruto: 10000, tara: 2000); // +800 cta cte

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

test('un usuario no administrador no ve el saldo bancario ni los próximos cheques en el dashboard', function () {
    $user = User::factory()->create(['role' => 'administrativo']);

    $cuenta = CuentaBancaria::factory()->create(['saldo' => 50000]);
    Cheque::emitir($cuenta, [
        'numero' => '1',
        'banco' => 'Banco Nación',
        'beneficiario' => 'Fletero Confidencial',
        'fecha_pago' => now()->addDays(10)->toDateString(),
        'monto' => 5000,
    ]);

    $component = Livewire::actingAs($user)->test('pages::dashboard');

    expect($component->instance()->saldoBancario)->toBeNull()
        ->and($component->instance()->proximosCheques)->toHaveCount(0);

    $component->assertDontSee('Saldo en cuentas bancarias')
        ->assertDontSee('Fletero Confidencial');
});
