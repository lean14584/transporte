<?php

use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\Guia;
use App\Models\Transporte;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('un administrador puede editar los porcentajes de ganancia por defecto', function () {
    $admin = User::factory()->create(['role' => 'administrador']);

    Livewire::actingAs($admin)
        ->test('pages::transporte.configuracion.editar')
        ->set('porcentaje_ganancia_kilo', '15')
        ->set('porcentaje_ganancia_unidad', '20')
        ->call('guardar')
        ->assertHasNoErrors();

    $configuracion = Configuracion::actual();
    expect((float) $configuracion->porcentaje_ganancia_kilo)->toBe(15.0)
        ->and((float) $configuracion->porcentaje_ganancia_unidad)->toBe(20.0);
});

test('un administrador puede cargar nombre, dueño, dirección y logo de la empresa', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => 'administrador']);

    Livewire::actingAs($admin)
        ->test('pages::transporte.configuracion.editar')
        ->set('empresa_nombre', 'Transportes del Sur SRL')
        ->set('empresa_propietario', 'Juan Pérez')
        ->set('empresa_direccion', 'Ruta 9 Km 123, Rosario')
        ->set('logo', UploadedFile::fake()->image('logo.png'))
        ->call('guardar')
        ->assertHasNoErrors();

    $configuracion = Configuracion::actual();

    expect($configuracion->empresa_nombre)->toBe('Transportes del Sur SRL')
        ->and($configuracion->empresa_propietario)->toBe('Juan Pérez')
        ->and($configuracion->empresa_direccion)->toBe('Ruta 9 Km 123, Rosario')
        ->and($configuracion->logo_path)->not->toBeNull();

    Storage::disk('public')->assertExists($configuracion->logo_path);
    expect($configuracion->logoUrl())->not->toBeNull();
});

test('quitar logo borra el archivo y limpia logo_path', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => 'administrador']);

    Livewire::actingAs($admin)
        ->test('pages::transporte.configuracion.editar')
        ->set('logo', UploadedFile::fake()->image('logo.png'))
        ->call('guardar');

    $path = Configuracion::actual()->logo_path;
    Storage::disk('public')->assertExists($path);

    Livewire::actingAs($admin)
        ->test('pages::transporte.configuracion.editar')
        ->call('quitarLogo');

    Storage::disk('public')->assertMissing($path);
    expect(Configuracion::actual()->logo_path)->toBeNull();
});

test('un administrativo no puede acceder a la pantalla de configuración', function () {
    $administrativo = User::factory()->create(['role' => 'administrativo']);

    $this->actingAs($administrativo)
        ->get(route('configuracion.edit'))
        ->assertForbidden();
});

test('la recepción de una guía precarga el porcentaje configurado según kilos o unidades', function () {
    Configuracion::actual()->update([
        'porcentaje_ganancia_kilo' => '12.5',
        'porcentaje_ganancia_unidad' => '7.5',
    ]);

    $admin = User::factory()->create(['role' => 'administrador']);
    $cliente = Cliente::factory()->create();
    $transporte = Transporte::factory()->create();

    $guiaKilos = Guia::factory()->for($cliente)->for($transporte)->create(['unidad' => 'kilos']);
    $guiaUnidades = Guia::factory()->for($cliente)->for($transporte)->create(['unidad' => 'unidades']);

    Livewire::actingAs($admin)
        ->test('pages::transporte.guias.recepcion', ['guia' => $guiaKilos])
        ->assertSet('porcentaje_ganancia', '12.50');

    Livewire::actingAs($admin)
        ->test('pages::transporte.guias.recepcion', ['guia' => $guiaUnidades])
        ->assertSet('porcentaje_ganancia', '7.50');
});
