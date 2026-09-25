<?php

use App\Livewire\Concerns\FiltraPorRangoDeFechas;
use App\Models\Cliente;
use App\Models\Recibo;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Cuenta corriente')] class extends Component {
    use WithPagination;
    use FiltraPorRangoDeFechas;

    #[Locked]
    public Cliente $cliente;

    public string $buscar = '';

    public float $monto = 0;
    public string $medio_pago = 'efectivo';
    public string $concepto = '';

    public function mount(Cliente $cliente): void
    {
        $this->cliente = $cliente;
    }

    public function updatingBuscar(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function movimientos()
    {
        return $this->cliente->movimientosCtaCte()
            ->when($this->desde, fn ($query) => $query->whereDate('fecha', '>=', $this->desde))
            ->when($this->hasta, fn ($query) => $query->whereDate('fecha', '<=', $this->hasta))
            ->when($this->buscar, fn ($query) => $query->where('concepto', 'like', "%{$this->buscar}%"))
            ->latest('fecha')
            ->latest('id')
            ->paginate(15);
    }

    public function cobrar(): void
    {
        $validated = $this->validate([
            'monto' => ['required', 'numeric', 'gt:0'],
            'medio_pago' => ['required', 'in:efectivo,transferencia,cheque'],
            'concepto' => ['nullable', 'string', 'max:255'],
        ]);

        Recibo::cobrar($this->cliente, (float) $validated['monto'], $validated['medio_pago'], $validated['concepto'] ?: null);

        $this->cliente->refresh();
        $this->reset(['monto', 'concepto']);
        $this->medio_pago = 'efectivo';

        unset($this->movimientos);
        Flux::modal('cobrar-form')->close();

        Flux::toast(variant: 'success', text: __('Cobro registrado.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $cliente->nombre }}</flux:heading>
            <flux:subheading>{{ __('Cuenta corriente') }}</flux:subheading>
        </div>

        <flux:modal.trigger name="cobrar-form">
            <flux:button variant="primary" icon="banknotes">
                {{ __('Registrar cobro') }}
            </flux:button>
        </flux:modal.trigger>
    </div>

    @php
        $saldoPositivo = $cliente->saldo_cta_cte > 0;
        $saldoNegativo = $cliente->saldo_cta_cte < 0;
    @endphp
    <div class="rounded-xl border p-4 {{ $saldoPositivo ? 'border-red-200 bg-red-50 dark:border-red-900/50 dark:bg-red-950/30' : ($saldoNegativo ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/50 dark:bg-emerald-950/30' : 'border-zinc-200 dark:border-white/10') }}">
        <flux:text size="sm" class="{{ $saldoPositivo ? 'text-red-900/70 dark:text-red-300' : ($saldoNegativo ? 'text-emerald-900/70 dark:text-emerald-300' : '') }}">
            {{ __('Saldo actual') }}
        </flux:text>
        <flux:heading size="xl" class="mt-1 font-mono {{ $saldoPositivo ? 'text-red-900 dark:text-red-100' : ($saldoNegativo ? 'text-emerald-900 dark:text-emerald-100' : '') }}">
            $ {{ number_format((float) $cliente->saldo_cta_cte, 2, ',', '.') }}
        </flux:heading>
    </div>

    <div class="flex flex-wrap items-end gap-4">
        <flux:input wire:model.live.debounce.300ms="buscar" icon="magnifying-glass" placeholder="{{ __('Buscar por concepto...') }}" class="max-w-sm" />
    </div>

    <x-filtro-rango-fechas :desde="$desde" :hasta="$hasta" :rango-invertido="$this->rangoFechasInvertido()" />

    <div class="hidden overflow-x-auto md:block">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Fecha') }}</flux:table.column>
                <flux:table.column>{{ __('Concepto') }}</flux:table.column>
                <flux:table.column>{{ __('Tipo') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Monto') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Saldo') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->movimientos as $movimiento)
                    <flux:table.row wire:key="movimiento-{{ $movimiento->id }}">
                        <flux:table.cell>{{ $movimiento->fecha->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>{{ $movimiento->concepto }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$movimiento->tipo === 'cargo' ? 'red' : 'green'">
                                {{ $movimiento->tipo === 'cargo' ? __('Cargo') : __('Pago') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end" class="font-mono">
                            $ {{ number_format((float) $movimiento->monto, 2, ',', '.') }}
                        </flux:table.cell>
                        <flux:table.cell align="end" class="font-mono">
                            $ {{ number_format((float) $movimiento->saldo, 2, ',', '.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-zinc-500">
                            {{ __('Todavía no hay movimientos.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div class="grid gap-3 md:hidden">
        @forelse ($this->movimientos as $movimiento)
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-white/10" wire:key="movimiento-mobile-{{ $movimiento->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm text-zinc-500">{{ $movimiento->fecha->format('d/m/Y') }}</div>
                        <div class="font-medium">{{ $movimiento->concepto }}</div>
                    </div>
                    <flux:badge size="sm" :color="$movimiento->tipo === 'cargo' ? 'red' : 'green'">
                        {{ $movimiento->tipo === 'cargo' ? __('Cargo') : __('Pago') }}
                    </flux:badge>
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-zinc-100 pt-3 text-sm dark:border-white/5">
                    <span class="font-mono">$ {{ number_format((float) $movimiento->monto, 2, ',', '.') }}</span>
                    <span class="font-mono text-zinc-500">{{ __('Saldo') }}: $ {{ number_format((float) $movimiento->saldo, 2, ',', '.') }}</span>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-zinc-200 p-4 text-center text-zinc-500 dark:border-white/10">
                {{ __('Todavía no hay movimientos.') }}
            </div>
        @endforelse
    </div>

    {{ $this->movimientos->links() }}

    <flux:modal name="cobrar-form" class="max-w-lg">
        <form wire:submit="cobrar" class="space-y-6">
            <flux:heading size="lg">{{ __('Registrar cobro') }}</flux:heading>
            <flux:subheading>{{ $cliente->nombre }}</flux:subheading>

            <flux:input wire:model="monto" :label="__('Monto')" type="number" step="0.01" min="0.01" required autofocus />

            <flux:select wire:model="medio_pago" :label="__('Medio de pago')">
                <flux:select.option value="efectivo">{{ __('Efectivo') }}</flux:select.option>
                <flux:select.option value="transferencia">{{ __('Transferencia') }}</flux:select.option>
                <flux:select.option value="cheque">{{ __('Cheque') }}</flux:select.option>
            </flux:select>

            <flux:input wire:model="concepto" :label="__('Concepto')" placeholder="{{ __('Opcional') }}" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="cobrar">{{ __('Cobrar') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
