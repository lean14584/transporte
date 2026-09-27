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

test('la caja no cuenta cobros ni pagos con cheque rechazado en el debe/haber ni en el listado', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create();
    $fletero = Fletero::factory()->create();
    $cuentaBancaria = \App\Models\CuentaBancaria::factory()->create(['saldo' => 100000]);

    Recibo::cobrar($cliente, 15000, 'efectivo', 'Cobro válido');
    $reciboRechazado = Recibo::cobrar($cliente, 9000, 'cheque', 'Cobro con cheque rechazado');
    Recibo::rechazarCheque($reciboRechazado);

    OrdenPago::emitir($fletero, 4000, 'efectivo', 'Pago válido');
    $cheque = \App\Models\Cheque::emitir($cuentaBancaria, [
        'numero' => '001',
        'banco' => 'Banco QA',
        'beneficiario' => $fletero->razon_social,
        'fecha_pago' => now()->addDays(30)->toDateString(),
        'monto' => 6000,
    ]);
    OrdenPago::emitir($fletero, 6000, 'cheque', 'Pago con cheque rechazado', $cheque->id);
    \App\Models\Cheque::rechazar($cheque);

    $response = $this->actingAs($user)
        ->get(route('caja.index'))
        ->assertOk()
        ->assertSee('Cobro válido')
        ->assertSee('Pago válido')
        ->assertDontSee('Cobro con cheque rechazado')
        ->assertDontSee('Pago con cheque rechazado');

    $response->assertSee('15.000,00'); // total debe: solo el cobro válido
    $response->assertSee('4.000,00');  // total haber: solo el pago válido
    $response->assertSee('11.000,00'); // saldo: 15000-4000
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

test('la caja avisa cuando el rango de fechas está invertido', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::transporte.caja.index')
        ->set('desde', now()->toDateString())
        ->set('hasta', now()->subDays(5)->toDateString())
        ->assertSee('rango de fechas está invertido');
});
