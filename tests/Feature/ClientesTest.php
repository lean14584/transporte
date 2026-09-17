<?php

use App\Models\Cliente;
use App\Models\User;
use Livewire\Livewire;

test('un usuario autenticado puede administrar clientes y su cuenta corriente', function () {
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create(['nombre' => 'Acopio Central', 'saldo_cta_cte' => 1500]);

    $this->actingAs($user)
        ->get(route('clientes.index'))
        ->assertOk()
        ->assertSee('Acopio Central');

    $this->actingAs($user)
        ->get(route('clientes.cta-cte', $cliente))
        ->assertOk()
        ->assertSee('Acopio Central');

    Livewire::actingAs($user)
        ->test('pages::transporte.clientes.cta-cte', ['cliente' => $cliente])
        ->set('monto', 500)
        ->set('medio_pago', 'efectivo')
        ->call('cobrar')
        ->assertHasNoErrors();

    expect($cliente->refresh()->saldo_cta_cte)->toEqual('1000.00');
});
