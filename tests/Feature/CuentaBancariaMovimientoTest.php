<?php

use App\Models\Cheque;
use App\Models\CuentaBancaria;
use App\Models\CuentaBancariaMovimiento;
use App\Models\User;
use Livewire\Livewire;

test('abrir una cuenta con saldo inicial deja un movimiento de apertura', function () {
    $cuenta = CuentaBancaria::abrir([
        'banco' => 'Banco Test',
        'numero_cuenta' => '123',
        'alias_cbu' => null,
        'saldo' => 50000,
    ]);

    $movimiento = CuentaBancariaMovimiento::where('cuenta_bancaria_id', $cuenta->id)->firstOrFail();

    expect($movimiento->tipo)->toBe('ingreso')
        ->and($movimiento->concepto)->toBe('Apertura de cuenta')
        ->and((float) $movimiento->monto)->toBe(50000.0)
        ->and((float) $movimiento->saldo)->toBe(50000.0);
});

test('abrir una cuenta con saldo inicial cero no crea movimiento', function () {
    $cuenta = CuentaBancaria::abrir([
        'banco' => 'Banco Test',
        'numero_cuenta' => '123',
        'alias_cbu' => null,
        'saldo' => 0,
    ]);

    expect(CuentaBancariaMovimiento::where('cuenta_bancaria_id', $cuenta->id)->count())->toBe(0);
});

test('emitir un cheque registra un movimiento de egreso ligado al cheque', function () {
    $cuenta = CuentaBancaria::factory()->create(['saldo' => 100000]);

    $cheque = Cheque::emitir($cuenta, [
        'numero' => '1',
        'banco' => 'Banco Test',
        'beneficiario' => 'Proveedor',
        'fecha_pago' => now()->addMonth()->toDateString(),
        'monto' => 30000,
    ]);

    $movimiento = CuentaBancariaMovimiento::where('cheque_id', $cheque->id)->firstOrFail();

    expect($movimiento->tipo)->toBe('egreso')
        ->and($movimiento->concepto)->toBe("Emisión cheque {$cheque->numero}")
        ->and((float) $movimiento->monto)->toBe(30000.0)
        ->and((float) $movimiento->saldo)->toBe(70000.0)
        ->and($cuenta->refresh()->saldo)->toEqual('70000.00');
});

test('rechazar un cheque registra un movimiento de ingreso que revierte la emisión', function () {
    $cuenta = CuentaBancaria::factory()->create(['saldo' => 100000]);

    $cheque = Cheque::emitir($cuenta, [
        'numero' => '1',
        'banco' => 'Banco Test',
        'beneficiario' => 'Proveedor',
        'fecha_pago' => now()->addMonth()->toDateString(),
        'monto' => 30000,
    ]);

    $movimientoEmision = CuentaBancariaMovimiento::where('cheque_id', $cheque->id)->where('tipo', 'egreso')->firstOrFail();

    Cheque::rechazar($cheque->refresh());

    $movimientoRechazo = CuentaBancariaMovimiento::where('cheque_id', $cheque->id)->where('tipo', 'ingreso')->firstOrFail();

    expect($movimientoRechazo->revierte_movimiento_id)->toBe($movimientoEmision->id)
        ->and((float) $movimientoRechazo->monto)->toBe(30000.0)
        ->and((float) $movimientoRechazo->saldo)->toBe(100000.0)
        ->and($cuenta->refresh()->saldo)->toEqual('100000.00');
});

test('un administrador puede hacer un ajuste manual de saldo con motivo', function () {
    $admin = User::factory()->create(['role' => 'administrador']);
    $cuenta = CuentaBancaria::factory()->create(['saldo' => 10000]);

    Livewire::actingAs($admin)
        ->test('pages::transporte.cuentas-bancarias.index')
        ->call('abrirAjuste', $cuenta->id)
        ->set('ajuste_tipo', 'egreso')
        ->set('ajuste_monto', '4000')
        ->set('ajuste_motivo', 'Comisión bancaria no cargada en el sistema')
        ->call('guardarAjuste')
        ->assertHasNoErrors();

    expect($cuenta->refresh()->saldo)->toEqual('6000.00');

    $movimiento = CuentaBancariaMovimiento::where('cuenta_bancaria_id', $cuenta->id)->firstOrFail();

    expect($movimiento->tipo)->toBe('egreso')
        ->and($movimiento->concepto)->toBe('Ajuste manual: Comisión bancaria no cargada en el sistema')
        ->and((float) $movimiento->monto)->toBe(4000.0);
});

test('un ajuste manual sin motivo o que deja el saldo negativo no se guarda', function () {
    $admin = User::factory()->create(['role' => 'administrador']);
    $cuenta = CuentaBancaria::factory()->create(['saldo' => 1000]);

    Livewire::actingAs($admin)
        ->test('pages::transporte.cuentas-bancarias.index')
        ->call('abrirAjuste', $cuenta->id)
        ->set('ajuste_tipo', 'egreso')
        ->set('ajuste_monto', '4000')
        ->set('ajuste_motivo', 'abc')
        ->call('guardarAjuste')
        ->assertHasErrors('ajuste_motivo');

    Livewire::actingAs($admin)
        ->test('pages::transporte.cuentas-bancarias.index')
        ->call('abrirAjuste', $cuenta->id)
        ->set('ajuste_tipo', 'egreso')
        ->set('ajuste_monto', '4000')
        ->set('ajuste_motivo', 'Ajuste que dejaría el saldo negativo')
        ->call('guardarAjuste')
        ->assertHasErrors('ajuste_monto');

    expect($cuenta->refresh()->saldo)->toEqual('1000.00')
        ->and(CuentaBancariaMovimiento::where('cuenta_bancaria_id', $cuenta->id)->count())->toBe(0);
});

test('la pantalla de movimientos de cuenta bancaria muestra el historial', function () {
    $admin = User::factory()->create(['role' => 'administrador']);
    $cuenta = CuentaBancaria::factory()->create(['saldo' => 100000]);

    Cheque::emitir($cuenta, [
        'numero' => '1',
        'banco' => 'Banco Test',
        'beneficiario' => 'Proveedor',
        'fecha_pago' => now()->addMonth()->toDateString(),
        'monto' => 15000,
    ]);

    Livewire::actingAs($admin)
        ->test('pages::transporte.cuentas-bancarias.movimientos', ['cuentaBancaria' => $cuenta])
        ->assertSee('Emisión cheque 1')
        ->assertSee('85.000,00');
});
