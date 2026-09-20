<?php

use App\Models\Fletero;
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
