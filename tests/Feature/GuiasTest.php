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

test('cargar una guía por unidades pide precio cliente/fletero pero no TN cargadas', function () {
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
        ->set('precio_cliente', '120')
        ->set('precio_fletero', '80')
        ->call('guardar')
        ->assertHasNoErrors();

    $guia = Guia::firstOrFail();

    expect($guia->cantidad)->toEqual('500.00')
        ->and($guia->tn_cargadas)->toBeNull()
        ->and($guia->precio_cliente)->toEqual('120.00')
        ->and($guia->precio_fletero)->toEqual('80.00');
});

test('cargar una guía por unidades sin precio cliente/fletero falla la validación', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::transporte.guias.crear')
        ->call('seleccionarTransporte', Transporte::factory()->create()->id)
        ->call('seleccionarCliente', Cliente::factory()->create()->id)
        ->call('seleccionarFletero', Fletero::factory()->create()->id)
        ->call('seleccionarProducto', Producto::factory()->create()->id)
        ->set('unidad', 'unidades')
        ->set('cantidad', '500')
        ->call('guardar')
        ->assertHasErrors(['precio_cliente', 'precio_fletero']);
});

test('recepcionar una guía por kilos carga TN descargadas a mano, calcula la pérdida y carga Total Cliente $ a la cta cte del cliente y Total Flete $ a la del fletero', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create();
    $fletero = Fletero::factory()->create();
    $guia = Guia::factory()->for($cliente)->for(Transporte::factory())->for($fletero)->create([
        'unidad' => 'kilos',
        'cantidad' => 12500,
        'tn_cargadas' => 12500,
        'precio_cliente' => 50,
        'precio_fletero' => 30,
    ]);

    $component = Livewire::actingAs($user)
        ->test('pages::transporte.guias.recepcion', ['guia' => $guia])
        ->assertSee('TN Descargadas')
        ->assertDontSee('Bruto (kg)')
        ->set('tn_descargadas', '12000');

    expect($component->instance()->perdida)->toBe(500.0)
        ->and($component->instance()->totalCliente)->toBe(600000.0)
        ->and($component->instance()->totalFletero)->toBe(360000.0);

    $component
        ->call('recepcionar')
        ->assertHasNoErrors();

    $guia->refresh();

    expect($guia->estado)->toBe('recibida')
        ->and($guia->neto)->toEqual('12000.00')
        ->and($guia->total_cliente)->toEqual('600000.00')
        ->and($guia->total_fletero)->toEqual('360000.00')
        ->and($cliente->refresh()->saldo_cta_cte)->toEqual('600000.00')
        ->and($fletero->refresh()->saldo_cta_cte)->toEqual('360000.00');

    $this->actingAs($user)
        ->get(route('guias.index'))
        ->assertOk()
        ->assertSee('Recibida');
});

test('recepcionar una guía por unidades cruza el precio cliente/fletero cargado con la cantidad realmente recibida, igual que por tonelada', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create();
    $fletero = Fletero::factory()->create();
    $guia = Guia::factory()->for($cliente)->for(Transporte::factory())->for($fletero)->create([
        'unidad' => 'unidades',
        'cantidad' => 500,
        'precio_cliente' => 120,
        'precio_fletero' => 80,
    ]);

    $component = Livewire::actingAs($user)
        ->test('pages::transporte.guias.recepcion', ['guia' => $guia])
        ->assertSee('unidades Descargadas')
        ->assertDontSee('Bruto (kg)')
        ->set('tn_descargadas', '480');

    expect($component->instance()->perdida)->toBe(20.0)
        ->and($component->instance()->totalCliente)->toBe(57600.0)
        ->and($component->instance()->totalFletero)->toBe(38400.0);

    $component->call('recepcionar')->assertHasNoErrors();

    $guia->refresh();

    expect($guia->estado)->toBe('recibida')
        ->and($guia->neto)->toEqual('480.00')
        ->and($guia->total_cliente)->toEqual('57600.00')
        ->and($guia->total_fletero)->toEqual('38400.00')
        ->and($cliente->refresh()->saldo_cta_cte)->toEqual('57600.00')
        ->and($fletero->refresh()->saldo_cta_cte)->toEqual('38400.00');
});
