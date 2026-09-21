<?php

use App\Models\Fletero;
use App\Models\PagoFletero;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Cuenta corriente')] class extends Component {
    use WithPagination;

    #[Locked]
    public Fletero $fletero;

    public float $monto = 0;
    public string $medio_pago = 'efectivo';
    public string $concepto = '';

    public function mount(Fletero $fletero): void
    {
        $this->fletero = $fletero;
    }

    #[Computed]
    public function movimientos()
    {
        return $this->fletero->movimientosCtaCte()->latest('fecha')->latest('id')->paginate(15);
    }

    public function pagar(): void
    {
        $validated = $this->validate([
            'monto' => ['required', 'numeric', 'gt:0'],
            'medio_pago' => ['required', 'in:efectivo,transferencia,cheque'],
            'concepto' => ['nullable', 'string', 'max:255'],
        ]);

        PagoFletero::pagar($this->fletero, (float) $validated['monto'], $validated['medio_pago'], $validated['concepto'] ?: null);

        $this->fletero->refresh();
        $this->reset(['monto', 'concepto']);
        $this->medio_pago = 'efectivo';

        unset($this->movimientos);
        Flux::modal('pagar-form')->close();

        Flux::toast(variant: 'success', text: __('Pago registrado.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $fletero->razon_social }}</flux:heading>
            <flux:subheading>{{ __('Cuenta corriente') }}</flux:subheading>
        </div>

        <flux:modal.trigger name="pagar-form">
            <flux:button variant="primary" icon="banknotes">
                {{ __('Registrar pago') }}
            </flux:button>
        </flux:modal.trigger>
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

    <flux:modal name="pagar-form" class="max-w-lg">
        <form wire:submit="pagar" class="space-y-6">
            <flux:heading size="lg">{{ __('Registrar pago') }}</flux:heading>
            <flux:subheading>{{ $fletero->razon_social }}</flux:subheading>

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

                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="pagar">{{ __('Pagar') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
