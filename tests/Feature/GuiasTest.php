<?php

use App\Models\Cliente;
use App\Models\Fletero;
use App\Models\Guia;
use App\Models\Producto;
use App\Models\Transporte;
use App\Models\User;
use Livewire\Livewire;

test('se puede cargar una guía buscando transporte, cliente, fletero y producto por autocompletado', function () {
    $user = User::factory()->create();
    $transporte = Transporte::factory()->create(['nombre' => 'Transporte Rápido SRL']);
    $cliente = Cliente::factory()->create(['nombre' => 'Cerealera del Norte']);
    $fletero = Fletero::factory()->create(['razon_social' => 'Juan Pérez', 'dni' => '30111222']);
    $producto = Producto::factory()->create(['nombre' => 'Soja']);

    Livewire::actingAs($user)
        ->test('pages::transporte.guias.crear')
        ->set('buscarTransporte', 'Rápido')
        ->assertSee('Transporte Rápido SRL')
        ->call('seleccionarTransporte', $transporte->id)
        ->set('buscarCliente', 'Cerealera')
        ->assertSee('Cerealera del Norte')
        ->call('seleccionarCliente', $cliente->id)
        ->set('buscarFletero', 'Juan')
        ->assertSee('Juan Pérez')
        ->call('seleccionarFletero', $fletero->id)
        ->set('buscarProducto', 'Soja')
        ->assertSee('Soja')
        ->call('seleccionarProducto', $producto->id)
        ->set('unidad', 'kilos')
        ->set('tn_cargadas', '25.5')
        ->set('precio_cliente', '50000')
        ->set('precio_fletero', '30000')
        ->call('guardar')
        ->assertHasNoErrors();

    $guia = Guia::firstOrFail();

    expect($guia->transporte_id)->toBe($transporte->id)
        ->and($guia->cliente_id)->toBe($cliente->id)
        ->and($guia->fletero_id)->toBe($fletero->id)
        ->and($guia->producto_id)->toBe($producto->id)
        ->and($guia->mercaderia)->toBe('Soja')
        ->and($guia->transportista)->toBe('Juan Pérez')
        ->and($guia->transportista_documento)->toBe('30111222')
        ->and($guia->cantidad)->toEqual('25.50')
        ->and($guia->tn_cargadas)->toEqual('25.50')
        ->and($guia->precio_cliente)->toEqual('50000.00')
        ->and($guia->precio_fletero)->toEqual('30000.00')
        ->and($guia->estado)->toBe('cargada');
});

test('cargar una guía por unidades no pide TN, solo cantidad', function () {
    $user = User::factory()->create();
    $transporte = Transporte::factory()->create();
    $cliente = Cliente::factory()->create();
    $fletero = Fletero::factory()->create();
    $producto = Producto::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::transporte.guias.crear')
        ->call('seleccionarTransporte', $transporte->id)
        ->call('seleccionarCliente', $cliente->id)
        ->call('seleccionarFletero', $fletero->id)
        ->call('seleccionarProducto', $producto->id)
        ->set('unidad', 'unidades')
        ->set('cantidad', '500')
        ->call('guardar')
        ->assertHasNoErrors();

    $guia = Guia::firstOrFail();

    expect($guia->cantidad)->toEqual('500.00')
        ->and($guia->tn_cargadas)->toBeNull()
        ->and($guia->precio_cliente)->toBeNull()
        ->and($guia->precio_fletero)->toBeNull();
});

test('recepcionar una guía por kilos precarga TN descargadas (bruto - tara), calcula la pérdida y carga los totales a mano en cta cte', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create();
    $guia = Guia::factory()->for($cliente)->for(Transporte::factory())->create(['unidad' => 'kilos', 'tn_cargadas' => 12500]);

    $component = Livewire::actingAs($user)
        ->test('pages::transporte.guias.recepcion', ['guia' => $guia])
        ->assertSee('Bruto (kg)')
        ->set('pesaje_bruto', '20000')
        ->set('tara', '8000');

    expect($component->get('tn_descargadas'))->toBe('12000')
        ->and($component->instance()->perdida)->toBe(500.0);

    $component
        ->set('total_cliente', '600000')
        ->set('total_fletero', '360000')
        ->set('monto_ganancia', '1200')
        ->call('recepcionar')
        ->assertHasNoErrors();

    $guia->refresh();

    expect($guia->estado)->toBe('recibida')
        ->and($guia->neto)->toEqual('12000.00')
        ->and($guia->total_cliente)->toEqual('600000.00')
        ->and($guia->total_fletero)->toEqual('360000.00')
        ->and($guia->monto_ganancia)->toEqual('1200.00')
        ->and($cliente->refresh()->saldo_cta_cte)->toEqual('1200.00');

    $this->actingAs($user)
        ->get(route('guias.index'))
        ->assertOk()
        ->assertSee('Recibida');
});

test('recepcionar una guía por unidades usa la cantidad recibida como neto', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create();
    $guia = Guia::factory()->for($cliente)->for(Transporte::factory())->create([
        'unidad' => 'unidades',
        'cantidad' => 500,
    ]);

    $component = Livewire::actingAs($user)
        ->test('pages::transporte.guias.recepcion', ['guia' => $guia])
        ->assertSee('Unidades recibidas')
        ->assertDontSee('Bruto (kg)');

    expect($component->get('cantidad_recibida'))->toBe('500');

    $component->set('cantidad_recibida', '480')
        ->set('porcentaje_ganancia', '10')
        ->call('recepcionar')
        ->assertHasNoErrors();

    $guia->refresh();

    expect($guia->estado)->toBe('recibida')
        ->and($guia->neto)->toEqual('480.00')
        ->and($guia->pesaje_bruto)->toBeNull()
        ->and($guia->tara)->toBeNull()
        ->and($cliente->refresh()->saldo_cta_cte)->toEqual('48.00');
});
