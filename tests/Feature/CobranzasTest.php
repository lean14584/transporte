<?php

use App\Models\Cliente;
use App\Models\CuentaCorrienteMovimiento;
use App\Models\Recibo;
use App\Models\User;
use Livewire\Livewire;

test('las cobranzas se pueden filtrar por rango de fechas', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create();

    Recibo::cobrar($cliente, 1000, 'efectivo', 'Cobro fuera de rango');
    Recibo::query()->latest('id')->first()->update(['fecha' => now()->subDays(10)->toDateString()]);

    Recibo::cobrar($cliente, 2000, 'efectivo', 'Cobro dentro de rango');

    Livewire::actingAs($user)
        ->test('pages::transporte.cobranzas.index')
        ->set('desde', now()->subDays(2)->toDateString())
        ->set('hasta', now()->toDateString())
        ->assertSee('Cobro dentro de rango')
        ->assertDontSee('Cobro fuera de rango');
});

test('marcar un cobro con cheque como rechazado revierte la deuda en la cta cte del cliente', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create();

    Recibo::cobrar($cliente, 5000, 'cheque', 'Pago con cheque');
    expect($cliente->refresh()->saldo_cta_cte)->toEqual('-5000.00');

    $recibo = Recibo::whereMedioPago('cheque')->firstOrFail();
    $movimientoOriginal = CuentaCorrienteMovimiento::where('recibo_id', $recibo->id)->firstOrFail();

    Livewire::actingAs($user)
        ->test('pages::transporte.cobranzas.index')
        ->call('marcarRechazado', $recibo->id)
        ->assertHasNoErrors();

    expect($recibo->refresh()->estado)->toBe('rechazado')
        ->and($cliente->refresh()->saldo_cta_cte)->toEqual('0.00');

    $movimientoReversion = CuentaCorrienteMovimiento::where('revierte_movimiento_id', $movimientoOriginal->id)->firstOrFail();

    expect($movimientoReversion->tipo)->toBe('cargo')
        ->and((float) $movimientoReversion->monto)->toBe(5000.0);
});

test('no se puede rechazar un cobro que no fue hecho con cheque, ni rechazar el mismo cheque dos veces', function () {
    $cliente = Cliente::factory()->create();

    $reciboEfectivo = Recibo::cobrar($cliente, 1000, 'efectivo');
    expect(fn () => Recibo::rechazarCheque($reciboEfectivo))->toThrow(RuntimeException::class);

    $reciboCheque = Recibo::cobrar($cliente, 2000, 'cheque');
    Recibo::rechazarCheque($reciboCheque);

    expect(fn () => Recibo::rechazarCheque($reciboCheque->refresh()))->toThrow(RuntimeException::class);
});
