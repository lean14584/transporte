<?php

use App\Livewire\Concerns\FiltraPorRangoDeFechas;
use App\Models\CuentaBancaria;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Movimientos de cuenta bancaria')] class extends Component {
    use WithPagination;
    use FiltraPorRangoDeFechas;

    #[Locked]
    public CuentaBancaria $cuentaBancaria;

    public function mount(CuentaBancaria $cuentaBancaria): void
    {
        abort_unless(Auth::user()->isAdministrador(), 403);

        $this->cuentaBancaria = $cuentaBancaria;
    }

    #[Computed]
    public function movimientos()
    {
        return $this->cuentaBancaria->movimientos()
            ->when($this->desde, fn ($query) => $query->whereDate('fecha', '>=', $this->desde))
            ->when($this->hasta, fn ($query) => $query->whereDate('fecha', '<=', $this->hasta))
            ->with('usuario')
            ->latest('fecha')
            ->latest('id')
            ->paginate(15);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $cuentaBancaria->banco }}</flux:heading>
            <flux:subheading>{{ __('Movimientos de la cuenta bancaria') }}</flux:subheading>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 p-4 dark:border-white/10">
        <flux:text size="sm">{{ __('Saldo actual') }}</flux:text>
        <flux:heading size="xl" class="mt-1 font-mono">
            $ {{ number_format((float) $cuentaBancaria->saldo, 2, ',', '.') }}
        </flux:heading>
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
                <flux:table.column>{{ __('Usuario') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->movimientos as $movimiento)
                    <flux:table.row wire:key="movimiento-{{ $movimiento->id }}">
                        <flux:table.cell>{{ $movimiento->fecha->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>{{ $movimiento->concepto }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$movimiento->tipo === 'egreso' ? 'red' : 'green'">
                                {{ $movimiento->tipo === 'egreso' ? __('Egreso') : __('Ingreso') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end" class="font-mono">
                            $ {{ number_format((float) $movimiento->monto, 2, ',', '.') }}
                        </flux:table.cell>
                        <flux:table.cell align="end" class="font-mono">
                            $ {{ number_format((float) $movimiento->saldo, 2, ',', '.') }}
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $movimiento->usuario?->name ?? '—' }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center text-zinc-500">
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
                        <div class="text-sm text-zinc-500">{{ $movimiento->usuario?->name ?? '—' }}</div>
                    </div>
                    <flux:badge size="sm" :color="$movimiento->tipo === 'egreso' ? 'red' : 'green'">
                        {{ $movimiento->tipo === 'egreso' ? __('Egreso') : __('Ingreso') }}
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
