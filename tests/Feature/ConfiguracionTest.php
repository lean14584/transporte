<?php

use App\Models\Configuracion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('un administrador puede cargar nombre, dueño, dirección y logo de la empresa', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => 'administrador']);

    Livewire::actingAs($admin)
        ->test('pages::transporte.configuracion.editar')
        ->set('empresa_nombre', 'Transportes del Sur SRL')
        ->set('empresa_subtitulo', 'Cargas generales en todo el país')
        ->set('empresa_propietario', 'Juan Pérez')
        ->set('empresa_direccion', 'Ruta 9 Km 123, Rosario')
        ->set('logo', UploadedFile::fake()->image('logo.png'))
        ->call('guardar')
        ->assertHasNoErrors();

    $configuracion = Configuracion::actual();

    expect($configuracion->empresa_nombre)->toBe('Transportes del Sur SRL')
        ->and($configuracion->empresa_subtitulo)->toBe('Cargas generales en todo el país')
        ->and($configuracion->subtituloPdf())->toBe('Cargas generales en todo el país')
        ->and($configuracion->empresa_propietario)->toBe('Juan Pérez')
        ->and($configuracion->empresa_direccion)->toBe('Ruta 9 Km 123, Rosario')
        ->and($configuracion->logo_path)->not->toBeNull();

    Storage::disk('public')->assertExists($configuracion->logo_path);
    expect($configuracion->logoUrl())->not->toBeNull();
});

test('el subtítulo del PDF cae a un default cuando no se configuró', function () {
    expect((new Configuracion)->subtituloPdf())->toBe('Transporte de cargas');
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
