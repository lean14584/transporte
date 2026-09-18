<?php

use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\Guia;
use App\Models\Transporte;
use App\Models\User;
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
