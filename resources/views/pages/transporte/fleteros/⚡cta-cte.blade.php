<?php

use App\Livewire\Concerns\FiltraPorRangoDeFechas;
use App\Models\Fletero;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Cuenta corriente')] class extends Component {
    use WithPagination;
    use FiltraPorRangoDeFechas;

    #[Locked]
    public Fletero $fletero;

    public string $buscar = '';

    public function mount(Fletero $fletero): void
    {
        abort_if(Auth::user()->isUsuarioCarga(), 403);

        $this->fletero = $fletero;
    }

    public function updatingBuscar(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function movimientos()
    {
        return $this->fletero->movimientosCtaCte()
            ->when($this->desde, fn ($query) => $query->whereDate('fecha', '>=', $this->desde))
            ->when($this->hasta, fn ($query) => $query->whereDate('fecha', '<=', $this->hasta))
            ->when($this->buscar, fn ($query) => $query->where('concepto', 'like', "%{$this->buscar}%"))
            ->latest('fecha')
            ->latest('id')
            ->paginate(15);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-page-header :title="$fletero->razon_social" :subtitle="__('Cuenta corriente')">
        <flux:button variant="primary" icon="banknotes" :href="route('ordenes-pago.index')" wire:navigate>
            {{ __('Registrar pago') }}
        </flux:button>
    </x-page-header>

    <div class="min-w-0 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
        <div class="flex items-center gap-3">
            <div class="rounded-lg bg-amber-100 p-2 dark:bg-amber-500/10">
                <flux:icon.banknotes class="size-5 text-amber-600 dark:text-amber-400" />
            </div>
            <flux:text size="sm">{{ __('Saldo actual (lo que la empresa le debe al fletero)') }}</flux:text>
        </div>
        <flux:heading size="xl" class="mt-3 font-mono">
            $ {{ number_format((float) $fletero->saldo_cta_cte, 2, ',', '.') }}
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
                            <flux:badge size="sm" :color="$movimiento->tipo === 'cargo' ? 'amber' : 'green'">
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
                    <flux:badge size="sm" :color="$movimiento->tipo === 'cargo' ? 'amber' : 'green'">
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
</div>
