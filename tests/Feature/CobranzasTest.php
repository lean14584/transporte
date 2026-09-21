<?php

use App\Models\Cliente;
use App\Models\Recibo;
use App\Models\User;
use Livewire\Livewire;

test('las cobranzas se pueden filtrar por rango de fechas', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create();

    Recibo::cobrar($cliente, 1000, 'efectivo', 'Cobro fuera de rango');
    Recibo::query()->latest('id')->first()->update(['fecha' => now()->subDays(10)->toDateString()]);

    Recibo::cobrar($cliente, 2000, 'efectivo', 'Cobro dentro de rango');

    Livewire::actingAs($user)
        ->test('pages::transporte.cobranzas.index')
        ->set('desde', now()->subDays(2)->toDateString())
        ->set('hasta', now()->toDateString())
        ->assertSee('Cobro dentro de rango')
        ->assertDontSee('Cobro fuera de rango');
});
