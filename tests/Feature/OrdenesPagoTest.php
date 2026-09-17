<?php

use App\Models\CuentaBancaria;
use App\Models\OrdenPago;
use App\Models\Transporte;
use App\Models\User;
use Livewire\Livewire;

test('emitir una orden de pago en efectivo no toca ninguna cuenta bancaria', function () {
    $user = User::factory()->create();
    $transporte = Transporte::factory()->create(['nombre' => 'Flete Norte']);

    Livewire::actingAs($user)
        ->test('pages::transporte.ordenes-pago.index')
        ->set('buscarTransporte', 'Flete')
        ->call('seleccionarTransporte', $transporte->id)
        ->set('monto', '5000')
        ->set('medio_pago', 'efectivo')
        ->call('emitir')
        ->assertHasNoErrors();

    $orden = OrdenPago::firstOrFail();

    expect($orden->transporte_id)->toBe($transporte->id)
        ->and($orden->medio_pago)->toBe('efectivo')
        ->and($orden->cheque_id)->toBeNull();
});

test('emitir una orden de pago con cheque descuenta el saldo de la cuenta bancaria', function () {
    $user = User::factory()->create();
    $transporte = Transporte::factory()->create();
    $cuenta = CuentaBancaria::factory()->create(['saldo' => 100000]);

    Livewire::actingAs($user)
        ->test('pages::transporte.ordenes-pago.index')
        ->call('seleccionarTransporte', $transporte->id)
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
        ->and($cuenta->refresh()->saldo)->toEqual('70000.00');
});
