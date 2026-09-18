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
