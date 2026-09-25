<?php

use App\Models\AuditLog;
use App\Models\Cliente;
use App\Models\User;
use Livewire\Livewire;

test('crear un cliente genera un audit log de tipo created con el usuario autor', function () {
    $user = User::factory()->create(['role' => 'administrador']);
    $this->actingAs($user);

    $cliente = Cliente::create([
        'numero_cliente' => '1',
        'nombre' => 'Cliente Test',
        'razon_social' => 'Cliente Test SA',
        'direccion' => 'Calle 123',
        'telefono' => '123456',
    ]);

    $log = AuditLog::where('auditable_type', Cliente::class)->where('auditable_id', $cliente->id)->firstOrFail();

    expect($log->event)->toBe('created')
        ->and($log->user_id)->toBe($user->id)
        ->and($log->changes['nombre']['new'])->toBe('Cliente Test')
        ->and($log->changes['nombre']['old'])->toBeNull();
});

test('editar un cliente genera un audit log de tipo updated solo con los campos que cambiaron', function () {
    $user = User::factory()->create(['role' => 'administrador']);
    $this->actingAs($user);

    $cliente = Cliente::create([
        'numero_cliente' => '1',
        'nombre' => 'Original',
        'razon_social' => 'Original SA',
        'direccion' => 'Calle 123',
        'telefono' => '123456',
    ]);

    AuditLog::where('auditable_id', $cliente->id)->delete();

    $cliente->update(['nombre' => 'Cambiado']);

    $log = AuditLog::where('auditable_type', Cliente::class)
        ->where('auditable_id', $cliente->id)
        ->where('event', 'updated')
        ->firstOrFail();

    expect($log->changes)->toHaveKey('nombre')
        ->and($log->changes['nombre']['old'])->toBe('Original')
        ->and($log->changes['nombre']['new'])->toBe('Cambiado')
        ->and($log->changes)->not->toHaveKey('direccion');
});

test('un update que no cambia nada no genera audit log', function () {
    $user = User::factory()->create(['role' => 'administrador']);
    $this->actingAs($user);

    $cliente = Cliente::create([
        'numero_cliente' => '1',
        'nombre' => 'Original',
        'razon_social' => 'Original SA',
        'direccion' => 'Calle 123',
        'telefono' => '123456',
    ]);

    AuditLog::query()->delete();

    $cliente->update(['nombre' => 'Original']);

    expect(AuditLog::where('auditable_id', $cliente->id)->where('event', 'updated')->count())->toBe(0);
});

test('borrar un cliente genera un audit log de tipo deleted', function () {
    $user = User::factory()->create(['role' => 'administrador']);
    $this->actingAs($user);

    $cliente = Cliente::create([
        'numero_cliente' => '1',
        'nombre' => 'A borrar',
        'razon_social' => 'A borrar SA',
        'direccion' => 'Calle 123',
        'telefono' => '123456',
    ]);

    $clienteId = $cliente->id;
    $cliente->delete();

    $log = AuditLog::where('auditable_type', Cliente::class)
        ->where('auditable_id', $clienteId)
        ->where('event', 'deleted')
        ->firstOrFail();

    expect($log->changes['nombre']['old'])->toBe('A borrar')
        ->and($log->changes['nombre']['new'])->toBeNull();
});

test('la contraseña de un usuario nunca queda en el diff de auditoría', function () {
    $admin = User::factory()->create(['role' => 'administrador']);
    $this->actingAs($admin);

    $nuevo = User::create([
        'name' => 'Nuevo',
        'username' => 'nuevo',
        'email' => 'nuevo@example.com',
        'password' => 'password123',
        'role' => 'administrativo',
    ]);

    $log = AuditLog::where('auditable_type', User::class)->where('auditable_id', $nuevo->id)->firstOrFail();

    expect($log->changes)->not->toHaveKey('password')
        ->and($log->changes)->not->toHaveKey('remember_token');
});

test('un administrador puede ver la pantalla de auditoria filtrada', function () {
    $admin = User::factory()->create(['role' => 'administrador']);
    $this->actingAs($admin);

    Cliente::create([
        'numero_cliente' => '1',
        'nombre' => 'Visible En Auditoria',
        'razon_social' => 'SA',
        'direccion' => 'Calle 123',
        'telefono' => '123456',
    ]);

    Livewire::actingAs($admin)
        ->test('pages::transporte.auditoria.index')
        ->assertSee('Cliente')
        ->set('modelo', \App\Models\Cheque::class)
        ->assertDontSee('Visible En Auditoria');
});
