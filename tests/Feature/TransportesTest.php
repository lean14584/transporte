<?php

use App\Models\Transporte;
use App\Models\User;
use Livewire\Livewire;

test('un usuario autenticado puede ver, crear, editar y borrar transportes', function () {
    $user = User::factory()->create();
    Transporte::factory()->create(['nombre' => 'Flete del Sur']);

    $this->actingAs($user)
        ->get(route('transportes.index'))
        ->assertOk()
        ->assertSee('Flete del Sur');

    Livewire::actingAs($user)
        ->test('pages::transporte.transportes.index')
        ->set('nombre', 'Nuevo Transporte SRL')
        ->set('direccion', 'Ruta 9 km 100')
        ->call('guardar')
        ->assertHasNoErrors();

    $creado = Transporte::where('nombre', 'Nuevo Transporte SRL')->firstOrFail();

    Livewire::actingAs($user)
        ->test('pages::transporte.transportes.index')
        ->call('editar', $creado->id)
        ->set('telefono', '3814000000')
        ->call('guardar')
        ->assertHasNoErrors();

    expect($creado->refresh()->telefono)->toBe('3814000000');

    Livewire::actingAs($user)
        ->test('pages::transporte.transportes.index')
        ->call('eliminar', $creado->id);

    expect(Transporte::find($creado->id))->toBeNull();
});
