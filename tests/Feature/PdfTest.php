<?php

use App\Models\Cliente;
use App\Models\Guia;
use App\Models\OrdenPago;
use App\Models\Recibo;
use App\Models\Transporte;
use App\Models\User;

test('se pueden generar los pdf de guía, recibo y orden de pago', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create();
    $transporte = Transporte::factory()->create();

    $guia = Guia::factory()->for($cliente)->for($transporte)->create();
    $guia->recepcionar(20000, 8000, 10);

    $recibo = Recibo::cobrar($cliente->refresh(), 500, 'efectivo');

    $orden = OrdenPago::create([
        'transporte_id' => $transporte->id,
        'fecha' => now()->toDateString(),
        'monto' => 1000,
        'medio_pago' => 'efectivo',
        'usuario_id' => $user->id,
    ]);

    $this->actingAs($user);

    $this->get(route('guias.pdf', $guia))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->get(route('recibos.pdf', $recibo))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->get(route('ordenes-pago.pdf', $orden))->assertOk()->assertHeader('content-type', 'application/pdf');
});

test('el folio de cada documento tiene el prefijo correcto', function () {
    $cliente = Cliente::factory()->create();
    $transporte = Transporte::factory()->create();

    $guia = Guia::factory()->for($cliente)->for($transporte)->create();
    $recibo = Recibo::cobrar($cliente, 100, 'efectivo');
    $orden = OrdenPago::create([
        'transporte_id' => $transporte->id,
        'fecha' => now()->toDateString(),
        'monto' => 100,
        'medio_pago' => 'efectivo',
    ]);

    expect($guia->folio())->toStartWith('G-')
        ->and($recibo->folio())->toStartWith('R-')
        ->and($orden->folio())->toStartWith('OP-');
});
