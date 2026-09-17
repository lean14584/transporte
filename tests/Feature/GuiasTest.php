<?php

use App\Models\Cliente;
use App\Models\Guia;
use App\Models\Transporte;
use App\Models\User;
use Livewire\Livewire;

test('se puede cargar una guía buscando transporte y cliente por autocompletado', function () {
    $user = User::factory()->create();
    $transporte = Transporte::factory()->create(['nombre' => 'Transporte Rápido SRL']);
    $cliente = Cliente::factory()->create(['nombre' => 'Cerealera del Norte']);

    Livewire::actingAs($user)
        ->test('pages::transporte.guias.crear')
        ->set('buscarTransporte', 'Rápido')
        ->assertSee('Transporte Rápido SRL')
        ->call('seleccionarTransporte', $transporte->id)
        ->set('buscarCliente', 'Cerealera')
        ->assertSee('Cerealera del Norte')
        ->call('seleccionarCliente', $cliente->id)
        ->set('transportista', 'Juan Pérez')
        ->set('mercaderia', 'Soja')
        ->set('cantidad', '25000')
        ->set('unidad', 'kilos')
        ->call('guardar')
        ->assertHasNoErrors();

    $guia = Guia::firstOrFail();

    expect($guia->transporte_id)->toBe($transporte->id)
        ->and($guia->cliente_id)->toBe($cliente->id)
        ->and($guia->estado)->toBe('cargada');
});

test('recepcionar una guía desde la pantalla actualiza estado y cta cte', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create();
    $guia = Guia::factory()->for($cliente)->for(Transporte::factory())->create();

    Livewire::actingAs($user)
        ->test('pages::transporte.guias.recepcion', ['guia' => $guia])
        ->set('pesaje_bruto', '20000')
        ->set('tara', '8000')
        ->set('porcentaje_ganancia', '10')
        ->call('recepcionar')
        ->assertHasNoErrors();

    $guia->refresh();

    expect($guia->estado)->toBe('recibida')
        ->and($guia->neto)->toEqual('12000.00')
        ->and($cliente->refresh()->saldo_cta_cte)->toEqual('1200.00');

    $this->actingAs($user)
        ->get(route('guias.index'))
        ->assertOk()
        ->assertSee('Recibida');
});
