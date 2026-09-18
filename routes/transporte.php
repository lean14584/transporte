<?php

use App\Http\Controllers\PdfController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('transportes', 'pages::transporte.transportes.index')->name('transportes.index');

    Route::livewire('clientes', 'pages::transporte.clientes.index')->name('clientes.index');
    Route::livewire('clientes/{cliente}/cta-cte', 'pages::transporte.clientes.cta-cte')->name('clientes.cta-cte');

    Route::livewire('guias', 'pages::transporte.guias.index')->name('guias.index');
    Route::livewire('guias/nueva', 'pages::transporte.guias.crear')->name('guias.crear');
    Route::livewire('guias/{guia}/recepcion', 'pages::transporte.guias.recepcion')->name('guias.recepcion');
    Route::get('guias/{guia}/pdf', [PdfController::class, 'guia'])->name('guias.pdf');

    Route::livewire('cuentas-bancarias', 'pages::transporte.cuentas-bancarias.index')->name('cuentas-bancarias.index');
    Route::livewire('cheques', 'pages::transporte.cheques.index')->name('cheques.index');

    Route::livewire('ordenes-pago', 'pages::transporte.ordenes-pago.index')->name('ordenes-pago.index');
    Route::get('ordenes-pago/{ordenPago}/pdf', [PdfController::class, 'ordenPago'])->name('ordenes-pago.pdf');

    Route::livewire('cobranzas', 'pages::transporte.cobranzas.index')->name('cobranzas.index');
    Route::get('recibos/{recibo}/pdf', [PdfController::class, 'recibo'])->name('recibos.pdf');

    Route::livewire('usuarios', 'pages::transporte.usuarios.index')->name('usuarios.index');

    Route::livewire('configuracion', 'pages::transporte.configuracion.editar')->name('configuracion.edit');
});
