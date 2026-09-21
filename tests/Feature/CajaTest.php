<?php

use App\Models\Cliente;
use App\Models\Fletero;
use App\Models\OrdenPago;
use App\Models\Recibo;
use App\Models\User;
use Livewire\Livewire;

test('la caja muestra cobranzas y órdenes de pago como debe y haber, con el saldo correcto', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create(['nombre' => 'Cliente Caja QA']);
    $fletero = Fletero::factory()->create(['razon_social' => 'Fletero Caja QA']);

    Recibo::cobrar($cliente, 15000, 'efectivo', 'Cobro guía 1');
    Recibo::cobrar($cliente, 5000, 'transferencia');
    OrdenPago::emitir($fletero, 8000, 'efectivo', 'Pago guía 1');

    $response = $this->actingAs($user)
        ->get(route('caja.index'))
        ->assertOk()
        ->assertSee('Cliente Caja QA')
        ->assertSee('Fletero Caja QA')
        ->assertSee('Cobro guía 1')
        ->assertSee('Pago guía 1');

    $response->assertSee('20.000,00'); // total debe: 15000+5000
    $response->assertSee('8.000,00');  // total haber
    $response->assertSee('12.000,00'); // saldo: 20000-8000
});

test('la caja se puede filtrar por rango de fechas', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create();
    $fletero = Fletero::factory()->create();

    Recibo::cobrar($cliente, 1000, 'efectivo', 'Cobro fuera de rango');
    Recibo::query()->latest('id')->first()->update(['fecha' => now()->subDays(10)->toDateString()]);

    Recibo::cobrar($cliente, 2000, 'efectivo', 'Cobro dentro de rango');
    OrdenPago::emitir($fletero, 500, 'efectivo', 'Pago dentro de rango');

    Livewire::actingAs($user)
        ->test('pages::transporte.caja.index')
        ->set('desde', now()->subDays(2)->toDateString())
        ->set('hasta', now()->toDateString())
        ->assertSee('Cobro dentro de rango')
        ->assertSee('Pago dentro de rango')
        ->assertDontSee('Cobro fuera de rango')
        ->assertSee('2.000,00') // total debe filtrado
        ->assertSee('500,00');  // total haber filtrado
});
