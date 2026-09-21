<?php

use App\Models\Cliente;
use App\Models\Fletero;
use App\Models\Guia;
use App\Models\Transporte;
use App\Models\User;
use Livewire\Livewire;

test('un usuario autenticado puede ver, crear, editar y borrar fleteros', function () {
    $user = User::factory()->create(['role' => 'administrador']);
    Fletero::factory()->create(['razon_social' => 'Fletes del Norte']);

    $this->actingAs($user)
        ->get(route('fleteros.index'))
        ->assertOk()
        ->assertSee('Fletes del Norte');

    Livewire::actingAs($user)
        ->test('pages::transporte.fleteros.index')
        ->set('numero_fletero', 'FLE-0001')
        ->set('razon_social', 'Nuevo Fletero SRL')
        ->set('cuit', '30-12345678-9')
        ->call('guardar')
        ->assertHasNoErrors();

    $creado = Fletero::where('razon_social', 'Nuevo Fletero SRL')->firstOrFail();

    Livewire::actingAs($user)
        ->test('pages::transporte.fleteros.index')
        ->call('editar', $creado->id)
        ->set('telefono', '3814000000')
        ->call('guardar')
        ->assertHasNoErrors();

    expect($creado->refresh()->telefono)->toBe('3814000000');

    Livewire::actingAs($user)
        ->test('pages::transporte.fleteros.index')
        ->call('eliminar', $creado->id);

    expect(Fletero::find($creado->id))->toBeNull();
});

test('la cuenta corriente del fletero muestra el saldo y los movimientos cargados por recepción de guías', function () {
    $user = User::factory()->create();
    $fletero = Fletero::factory()->create(['razon_social' => 'Fletes del Sur']);

    $this->actingAs($user)
        ->get(route('fleteros.cta-cte', $fletero))
        ->assertOk()
        ->assertSee('Fletes del Sur')
        ->assertSee('Todavía no hay movimientos.');

    $guia = Guia::factory()
        ->for(Cliente::factory())
        ->for(Transporte::factory())
        ->for($fletero)
        ->create(['unidad' => 'kilos', 'tn_cargadas' => 100, 'precio_cliente' => 1000, 'precio_fletero' => 600]);

    $guia->recepcionar(totalDescargado: 95, totalCliente: 95000, totalFletero: 57000);

    expect($fletero->refresh()->saldo_cta_cte)->toEqual('57000.00');

    $this->actingAs($user)
        ->get(route('fleteros.cta-cte', $fletero))
        ->assertOk()
        ->assertSee('57.000,00')
        ->assertSee('Cargo');
});
