<?php

use App\Models\Cliente;
use App\Models\Fletero;
use App\Models\Guia;
use App\Models\Recibo;
use App\Models\User;
use Livewire\Livewire;

test('un usuario de carga puede ver y crear guías', function () {
    $usuarioCarga = User::factory()->create(['role' => 'usuario_carga']);

    $this->actingAs($usuarioCarga)->get(route('guias.index'))->assertOk();
    $this->actingAs($usuarioCarga)->get(route('guias.crear'))->assertOk();
});

test('un usuario de carga no puede recepcionar guías', function () {
    $usuarioCarga = User::factory()->create(['role' => 'usuario_carga']);
    $guia = Guia::factory()->create(['estado' => 'cargada']);

    $this->actingAs($usuarioCarga)
        ->get(route('guias.recepcion', $guia))
        ->assertForbidden();
});

test('un usuario de carga puede ver y editar transportes, fleteros, productos y clientes pero no borrar', function () {
    $usuarioCarga = User::factory()->create(['role' => 'usuario_carga']);
    $cliente = Cliente::factory()->create();

    $this->actingAs($usuarioCarga)->get(route('transportes.index'))->assertOk();
    $this->actingAs($usuarioCarga)->get(route('fleteros.index'))->assertOk();
    $this->actingAs($usuarioCarga)->get(route('productos.index'))->assertOk();
    $this->actingAs($usuarioCarga)->get(route('clientes.index'))->assertOk();

    Livewire::actingAs($usuarioCarga)
        ->test('pages::transporte.clientes.index')
        ->call('eliminar', $cliente->id);

    expect(Cliente::whereKey($cliente->id)->exists())->toBeTrue();
});

test('un usuario de carga no puede acceder a caja, cobranzas, cta cte, cuentas bancarias, cheques, órdenes de pago, usuarios ni configuración', function () {
    $usuarioCarga = User::factory()->create(['role' => 'usuario_carga']);
    $cliente = Cliente::factory()->create();
    $fletero = Fletero::factory()->create();

    $this->actingAs($usuarioCarga)->get(route('caja.index'))->assertForbidden();
    $this->actingAs($usuarioCarga)->get(route('cobranzas.index'))->assertForbidden();
    $this->actingAs($usuarioCarga)->get(route('clientes.cta-cte', $cliente))->assertForbidden();
    $this->actingAs($usuarioCarga)->get(route('fleteros.cta-cte', $fletero))->assertForbidden();
    $this->actingAs($usuarioCarga)->get(route('cuentas-bancarias.index'))->assertForbidden();
    $this->actingAs($usuarioCarga)->get(route('cheques.index'))->assertForbidden();
    $this->actingAs($usuarioCarga)->get(route('ordenes-pago.index'))->assertForbidden();
    $this->actingAs($usuarioCarga)->get(route('usuarios.index'))->assertForbidden();
    $this->actingAs($usuarioCarga)->get(route('configuracion.edit'))->assertForbidden();
});

test('un usuario de carga no puede descargar el pdf de un recibo', function () {
    $usuarioCarga = User::factory()->create(['role' => 'usuario_carga']);
    $recibo = Recibo::factory()->create();

    $this->actingAs($usuarioCarga)
        ->get(route('recibos.pdf', $recibo))
        ->assertForbidden();
});

test('un administrador puede crear un usuario con rol usuario_carga', function () {
    $admin = User::factory()->create(['role' => 'administrador']);

    Livewire::actingAs($admin)
        ->test('pages::transporte.usuarios.index')
        ->set('name', 'Cargador Nuevo')
        ->set('username', 'cargador_nuevo')
        ->set('email', 'cargador@example.com')
        ->set('password', 'password123')
        ->set('role', 'usuario_carga')
        ->call('guardar')
        ->assertHasNoErrors();

    expect(User::where('email', 'cargador@example.com')->firstOrFail()->role)->toBe('usuario_carga');
});
