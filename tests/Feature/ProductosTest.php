<?php

use App\Models\Producto;
use App\Models\User;
use Livewire\Livewire;

test('un usuario autenticado puede ver, crear, editar y borrar productos', function () {
    $user = User::factory()->create(['role' => 'administrador']);
    Producto::factory()->create(['nombre' => 'Soja']);

    $this->actingAs($user)
        ->get(route('productos.index'))
        ->assertOk()
        ->assertSee('Soja');

    Livewire::actingAs($user)
        ->test('pages::transporte.productos.index')
        ->set('numero_producto', 'PRO-0001')
        ->set('nombre', 'Maíz')
        ->set('forma_de_carga', 'Granel')
        ->call('guardar')
        ->assertHasNoErrors();

    $creado = Producto::where('nombre', 'Maíz')->firstOrFail();

    Livewire::actingAs($user)
        ->test('pages::transporte.productos.index')
        ->call('editar', $creado->id)
        ->set('forma_de_carga', 'Bolsas')
        ->call('guardar')
        ->assertHasNoErrors();

    expect($creado->refresh()->forma_de_carga)->toBe('Bolsas');

    Livewire::actingAs($user)
        ->test('pages::transporte.productos.index')
        ->call('eliminar', $creado->id);

    expect(Producto::find($creado->id))->toBeNull();
});
