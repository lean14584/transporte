<?php

use App\Models\Cheque;
use App\Models\CuentaBancaria;
use App\Models\Fletero;
use App\Models\OrdenPago;
use Illuminate\Support\Facades\DB;

test('emitir un cheque con monto cero o negativo falla a nivel de modelo', function () {
    $cuenta = CuentaBancaria::factory()->create(['saldo' => 100000]);

    $emitir = fn (float $monto) => Cheque::emitir($cuenta, [
        'numero' => '1',
        'banco' => 'Banco Test',
        'beneficiario' => 'Fletero Test',
        'fecha_pago' => now()->addMonth()->toDateString(),
        'monto' => $monto,
    ]);

    expect(fn () => $emitir(0))->toThrow(RuntimeException::class)
        ->and(fn () => $emitir(-100))->toThrow(RuntimeException::class);

    expect(Cheque::count())->toBe(0)
        ->and($cuenta->refresh()->saldo)->toEqual('100000.00');
});

test('si algo falla después de emitir el cheque dentro de la misma transacción externa, todo se revierte', function () {
    $fletero = Fletero::factory()->create();
    $cuenta = CuentaBancaria::factory()->create(['saldo' => 100000]);

    // Reproduce la envoltura que usa ordenes-pago/⚡index.blade.php: Cheque::emitir()
    // y una segunda operación quedan dentro de UN solo DB::transaction externo.
    try {
        DB::transaction(function () use ($fletero, $cuenta) {
            Cheque::emitir($cuenta, [
                'numero' => '999',
                'banco' => 'Banco Test',
                'beneficiario' => $fletero->razon_social,
                'fecha_pago' => now()->addMonth()->toDateString(),
                'monto' => 30000,
            ]);

            throw new \Exception('Fallo simulado después de emitir el cheque, antes de crear la orden de pago');
        });
    } catch (\Exception $e) {
        // esperado: se relanza después del rollback
    }

    expect(Cheque::count())->toBe(0)
        ->and($cuenta->refresh()->saldo)->toEqual('100000.00');
});

test('rechazar un cheque emitido a un fletero revierte el pago en su cta cte', function () {
    $fletero = Fletero::factory()->create(['saldo_cta_cte' => 50000]);
    $cuenta = CuentaBancaria::factory()->create(['saldo' => 100000]);

    $cheque = Cheque::emitir($cuenta, [
        'numero' => '1',
        'banco' => 'Banco Test',
        'beneficiario' => $fletero->razon_social,
        'fecha_pago' => now()->addMonth()->toDateString(),
        'monto' => 50000,
    ]);

    OrdenPago::emitir($fletero, 50000, 'cheque', 'Pago flete', $cheque->id);

    expect($fletero->refresh()->saldo_cta_cte)->toEqual('0.00');

    Cheque::rechazar($cheque->refresh());

    expect($fletero->refresh()->saldo_cta_cte)->toEqual('50000.00')
        ->and($cuenta->refresh()->saldo)->toEqual('100000.00')
        ->and($cheque->refresh()->estado)->toBe('rechazado');
});

test('rechazar un cheque que no está ligado a ninguna orden de pago no rompe (solo devuelve el saldo bancario)', function () {
    $cuenta = CuentaBancaria::factory()->create(['saldo' => 100000]);

    $cheque = Cheque::emitir($cuenta, [
        'numero' => '2',
        'banco' => 'Banco Test',
        'beneficiario' => 'Proveedor Varios',
        'fecha_pago' => now()->addMonth()->toDateString(),
        'monto' => 10000,
    ]);

    Cheque::rechazar($cheque->refresh());

    expect($cuenta->refresh()->saldo)->toEqual('100000.00')
        ->and($cheque->refresh()->estado)->toBe('rechazado');
});
