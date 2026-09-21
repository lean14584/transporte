<?php

use App\Models\Cliente;
use App\Models\Guia;
use App\Models\Recibo;
use App\Models\Transporte;
use App\Models\User;

test('recepcionar una guía calcula el neto y carga la ganancia a la cta cte del cliente', function () {
    $cliente = Cliente::factory()->create();

    $guia = Guia::factory()->for($cliente)->for(Transporte::factory())->create();

    $guia->recepcionar(pesajeBruto: 20000, tara: 8000, totalDescargado: 12000, totalCliente: 0, totalFletero: 0, montoGanancia: 1200);

    $guia->refresh();
    $cliente->refresh();

    expect($guia->neto)->toEqual('12000.00')
        ->and($guia->monto_ganancia)->toEqual('1200.00')
        ->and($guia->estado)->toBe('recibida')
        ->and($cliente->saldo_cta_cte)->toEqual('1200.00');

    expect($guia->movimientoCtaCte->tipo)->toBe('cargo')
        ->and($guia->movimientoCtaCte->saldo)->toEqual('1200.00');
});

test('cobrar un recibo descuenta el pago del saldo de cta cte del cliente', function () {
    $cliente = Cliente::factory()->create(['saldo_cta_cte' => 1200]);

    $recibo = Recibo::cobrar($cliente, 500, 'efectivo');

    $cliente->refresh();

    expect($cliente->saldo_cta_cte)->toEqual('700.00')
        ->and($recibo->movimientoCtaCte->tipo)->toBe('pago')
        ->and($recibo->movimientoCtaCte->saldo)->toEqual('700.00');
});

test('varias guías y cobros consecutivos mantienen el saldo acumulado correcto', function () {
    $cliente = Cliente::factory()->create();
    $transporte = Transporte::factory()->create();

    Guia::factory()->for($cliente)->for($transporte)->create()
        ->recepcionar(pesajeBruto: 10000, tara: 2000, totalDescargado: 8000, totalCliente: 0, totalFletero: 0, montoGanancia: 800); // +800

    Guia::factory()->for($cliente)->for($transporte)->create()
        ->recepcionar(pesajeBruto: 5000, tara: 1000, totalDescargado: 4000, totalCliente: 0, totalFletero: 0, montoGanancia: 800); // +800

    Recibo::cobrar($cliente->refresh(), 1000, 'transferencia'); // -1000

    expect($cliente->refresh()->saldo_cta_cte)->toEqual('600.00');
});
