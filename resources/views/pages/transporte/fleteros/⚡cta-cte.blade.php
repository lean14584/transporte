<?php

use App\Models\Fletero;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Cuenta corriente')] class extends Component {
    use WithPagination;

    #[Locked]
    public Fletero $fletero;

    public function mount(Fletero $fletero): void
    {
        $this->fletero = $fletero;
    }

    #[Computed]
    public function movimientos()
    {
        return $this->fletero->movimientosCtaCte()->latest('fecha')->latest('id')->paginate(15);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $fletero->razon_social }}</flux:heading>
            <flux:subheading>{{ __('Cuenta corriente') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="banknotes" :href="route('ordenes-pago.index')" wire:navigate>
            {{ __('Registrar pago') }}
        </flux:button>
    </div>

    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/30">
        <flux:text size="sm" class="text-amber-900/70 dark:text-amber-300">
            {{ __('Saldo actual (lo que la empresa le debe al fletero)') }}
        </flux:text>
        <flux:heading size="xl" class="mt-1 font-mono text-amber-900 dark:text-amber-100">
            $ {{ number_format((float) $fletero->saldo_cta_cte, 2, ',', '.') }}
        </flux:heading>
    </div>

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
