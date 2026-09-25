<?php

use App\Models\CuentaBancaria;
use App\Models\Fletero;
use App\Models\OrdenPago;
use App\Models\User;
use Livewire\Livewire;

test('emitir una orden de pago en efectivo no toca ninguna cuenta bancaria y descuenta el saldo de la cta cte del fletero', function () {
    $user = User::factory()->create(['role' => 'administrador']);
    $fletero = Fletero::factory()->create(['razon_social' => 'Flete Norte', 'saldo_cta_cte' => 5000]);

    Livewire::actingAs($user)
        ->test('pages::transporte.ordenes-pago.index')
        ->set('buscarFletero', 'Flete')
        ->call('seleccionarFletero', $fletero->id)
        ->set('monto', '5000')
        ->set('medio_pago', 'efectivo')
        ->call('emitir')
        ->assertHasNoErrors();

    $orden = OrdenPago::firstOrFail();

    expect($orden->fletero_id)->toBe($fletero->id)
        ->and($orden->medio_pago)->toBe('efectivo')
        ->and($orden->cheque_id)->toBeNull()
        ->and($fletero->refresh()->saldo_cta_cte)->toEqual('0.00');

    expect($orden->movimientoCtaCte->tipo)->toBe('pago')
        ->and($orden->movimientoCtaCte->saldo)->toEqual('0.00');
});

test('emitir una orden de pago con cheque descuenta el saldo de la cuenta bancaria y de la cta cte del fletero', function () {
    $user = User::factory()->create(['role' => 'administrador']);
    $fletero = Fletero::factory()->create(['saldo_cta_cte' => 50000]);
    $cuenta = CuentaBancaria::factory()->create(['saldo' => 100000]);

    Livewire::actingAs($user)
        ->test('pages::transporte.ordenes-pago.index')
        ->call('seleccionarFletero', $fletero->id)
        ->set('monto', '30000')
        ->set('medio_pago', 'cheque')
        ->set('cuenta_bancaria_id', $cuenta->id)
        ->set('numero_cheque', '00012345')
        ->set('banco_cheque', 'Banco Nación')
        ->set('fecha_pago_cheque', now()->addMonth()->toDateString())
        ->call('emitir')
        ->assertHasNoErrors();

    $orden = OrdenPago::with('cheque')->firstOrFail();

    expect($orden->cheque)->not->toBeNull()
        ->and($orden->cheque->monto)->toEqual('30000.00')
        ->and($cuenta->refresh()->saldo)->toEqual('70000.00')
        ->and($fletero->refresh()->saldo_cta_cte)->toEqual('20000.00');
});

test('elegir la cuenta bancaria despues de un intento fallido limpia el error de ese campo', function () {
    $user = User::factory()->create(['role' => 'administrador']);
    $fletero = Fletero::factory()->create();
    $cuenta = CuentaBancaria::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::transporte.ordenes-pago.index')
        ->call('seleccionarFletero', $fletero->id)
        ->set('monto', '1000')
        ->set('medio_pago', 'cheque')
        ->call('emitir')
        ->assertHasErrors('cuenta_bancaria_id')
        ->set('cuenta_bancaria_id', $cuenta->id)
        ->assertHasNoErrors('cuenta_bancaria_id');
});

test('las órdenes de pago se pueden filtrar por rango de fechas', function () {
    $user = User::factory()->create(['role' => 'administrador']);
    $fletero = Fletero::factory()->create();

    OrdenPago::emitir($fletero, 1000, 'efectivo', 'Pago fuera de rango');
    OrdenPago::query()->latest('id')->first()->update(['fecha' => now()->subDays(10)->toDateString()]);

    OrdenPago::emitir($fletero, 2000, 'efectivo', 'Pago dentro de rango');

    Livewire::actingAs($user)
        ->test('pages::transporte.ordenes-pago.index')
        ->set('desde', now()->subDays(2)->toDateString())
        ->set('hasta', now()->toDateString())
        ->assertSee('Pago dentro de rango')
        ->assertDontSee('Pago fuera de rango');
});
