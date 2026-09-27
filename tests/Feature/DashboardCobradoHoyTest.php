<?php

use App\Models\Cliente;
use App\Models\Recibo;
use App\Models\User;
use Livewire\Livewire;

test('el dashboard no cuenta un recibo rechazado en cobrado hoy', function () {
    $admin = User::factory()->create(['role' => 'administrador']);
    $cliente = Cliente::factory()->create();

    Recibo::cobrar($cliente, 3000, 'efectivo', 'Cobro válido');
    $recibo = Recibo::cobrar($cliente, 7000, 'cheque', 'Cobro con cheque');
    Recibo::rechazarCheque($recibo);

    Livewire::actingAs($admin)
        ->test('pages::dashboard')
        ->assertSee('3.000,00')
        ->assertDontSee('10.000,00');
});
