<?php

use App\Models\User;
use Livewire\Livewire;

test('un administrador puede crear usuarios con rol', function () {
    $admin = User::factory()->create(['role' => 'administrador']);

    Livewire::actingAs($admin)
        ->test('pages::transporte.usuarios.index')
        ->set('name', 'Nuevo Empleado')
        ->set('username', 'nuevo_empleado')
        ->set('email', 'empleado@example.com')
        ->set('password', 'password123')
        ->set('role', 'administrativo')
        ->call('guardar')
        ->assertHasNoErrors();

    $creado = User::where('email', 'empleado@example.com')->firstOrFail();
    expect($creado->role)->toBe('administrativo');
});

test('un administrativo no puede acceder a la pantalla de usuarios', function () {
    $administrativo = User::factory()->create(['role' => 'administrativo']);

    $this->actingAs($administrativo)
        ->get(route('usuarios.index'))
        ->assertForbidden();
});

test('el último administrador no puede sacarse a sí mismo el rol de administrador', function () {
    $admin = User::factory()->create(['role' => 'administrador']);

    Livewire::actingAs($admin)
        ->test('pages::transporte.usuarios.index')
        ->call('editar', $admin->id)
        ->set('role', 'administrativo')
        ->call('guardar')
        ->assertHasErrors('role');

    expect($admin->refresh()->role)->toBe('administrador');
});

test('un administrador puede demover a otro administrador si queda al menos uno', function () {
    $admin = User::factory()->create(['role' => 'administrador']);
    $otroAdmin = User::factory()->create(['role' => 'administrador']);

    Livewire::actingAs($admin)
        ->test('pages::transporte.usuarios.index')
        ->call('editar', $otroAdmin->id)
        ->set('role', 'administrativo')
        ->call('guardar')
        ->assertHasNoErrors();

    expect($otroAdmin->refresh()->role)->toBe('administrativo');
});
