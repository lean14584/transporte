<?php

namespace App\Livewire\Concerns;

/**
 * Filtro Desde/Hasta reutilizado por Caja, Cobranzas y Órdenes de pago.
 * Requiere que el componente use también Livewire\WithPagination.
 */
trait FiltraPorRangoDeFechas
{
    public string $desde = '';
    public string $hasta = '';

    public function updatingDesde(): void
    {
        $this->resetPage();
    }

    public function updatingHasta(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltro(): void
    {
        $this->reset(['desde', 'hasta']);
        $this->resetPage();
    }

    public function rangoFechasInvertido(): bool
    {
        return $this->desde !== '' && $this->hasta !== '' && $this->desde > $this->hasta;
    }
}
