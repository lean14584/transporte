<?php

use App\Models\Cheque;
use App\Models\CuentaBancaria;
use App\Models\Fletero;
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
