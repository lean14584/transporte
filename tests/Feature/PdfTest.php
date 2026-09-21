<?php

use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\Fletero;
use App\Models\Guia;
use App\Models\OrdenPago;
use App\Models\Recibo;
use App\Models\Transporte;
use App\Models\User;

test('se pueden generar los pdf de guía, recibo y orden de pago', function () {
    $user = User::factory()->create(['role' => 'administrador']);
    $cliente = Cliente::factory()->create();
    $transporte = Transporte::factory()->create();

    $guia = Guia::factory()->for($cliente)->for($transporte)->create();
    $guia->recepcionar(totalDescargado: 12000, totalCliente: 600000, totalFletero: 360000, pesajeBruto: 20000, tara: 8000);

    $recibo = Recibo::cobrar($cliente->refresh(), 500, 'efectivo');

    $orden = OrdenPago::create([
        'fletero_id' => Fletero::factory()->create()->id,
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

test('el pdf de orden de pago no es accesible para un usuario no administrador', function () {
    $user = User::factory()->create(['role' => 'administrativo']);
    $orden = OrdenPago::create([
        'fletero_id' => Fletero::factory()->create()->id,
        'fecha' => now()->toDateString(),
        'monto' => 1000,
        'medio_pago' => 'efectivo',
        'usuario_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('ordenes-pago.pdf', $orden))
        ->assertForbidden();
});

test('el pdf de guía renderiza sin errores con nombre de empresa configurado', function () {
    Configuracion::actual()->update(['empresa_nombre' => 'Transportes del Sur SRL']);

    $user = User::factory()->create();
    $cliente = Cliente::factory()->create();
    $transporte = Transporte::factory()->create();
    $guia = Guia::factory()->for($cliente)->for($transporte)->create();

    $this->actingAs($user);

    $this->get(route('guias.pdf', $guia))->assertOk();
});

test('el folio de cada documento tiene el prefijo correcto', function () {
    $cliente = Cliente::factory()->create();
    $transporte = Transporte::factory()->create();

    $guia = Guia::factory()->for($cliente)->for($transporte)->create();
    $recibo = Recibo::cobrar($cliente, 100, 'efectivo');
    $orden = OrdenPago::create([
        'fletero_id' => Fletero::factory()->create()->id,
        'fecha' => now()->toDateString(),
        'monto' => 100,
        'medio_pago' => 'efectivo',
    ]);

    expect($guia->folio())->toStartWith('G-')
        ->and($recibo->folio())->toStartWith('R-')
        ->and($orden->folio())->toStartWith('OP-');
});
