<?php

use App\Models\Cliente;
use App\Models\Recibo;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Cuenta corriente')] class extends Component {
    use WithPagination;

    public Cliente $cliente;

    public float $monto = 0;
    public string $medio_pago = 'efectivo';
    public string $concepto = '';

    public function mount(Cliente $cliente): void
    {
        $this->cliente = $cliente;
    }

    #[Computed]
    public function movimientos()
    {
        return $this->cliente->movimientosCtaCte()->latest('fecha')->latest('id')->paginate(15);
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

        <div class="flex items-center gap-4">
            <div class="text-end">
                <flux:text size="sm">{{ __('Saldo') }}</flux:text>
                <flux:heading size="lg" class="{{ $cliente->saldo_cta_cte > 0 ? 'text-red-600 dark:text-red-400' : ($cliente->saldo_cta_cte < 0 ? 'text-green-600 dark:text-green-400' : '') }}">
                    $ {{ number_format((float) $cliente->saldo_cta_cte, 2, ',', '.') }}
                </flux:heading>
            </div>

            <flux:modal.trigger name="cobrar-form">
                <flux:button variant="primary" icon="banknotes">
                    {{ __('Registrar cobro') }}
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>

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

                <flux:button type="submit" variant="primary">{{ __('Cobrar') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
