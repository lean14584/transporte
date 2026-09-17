<?php

use App\Models\Recibo;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Cobranzas')] class extends Component {
    use WithPagination;

    #[Computed]
    public function recibos()
    {
        return Recibo::query()
            ->with('cliente')
            ->latest('fecha')
            ->latest('id')
            ->paginate(15);
    }

    #[Computed]
    public function totalDelDia(): float
    {
        return (float) Recibo::whereDate('fecha', now()->toDateString())->sum('monto');
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Cobranzas') }}</flux:heading>
            <flux:subheading>{{ __('Recibos emitidos a clientes. El cobro puntual se registra desde la cuenta corriente de cada cliente.') }}</flux:subheading>
        </div>

        <div class="text-end">
            <flux:text size="sm">{{ __('Cobrado hoy') }}</flux:text>
            <flux:heading size="lg" class="font-mono">$ {{ number_format($this->totalDelDia, 2, ',', '.') }}</flux:heading>
        </div>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Fecha') }}</flux:table.column>
            <flux:table.column>{{ __('Cliente') }}</flux:table.column>
            <flux:table.column>{{ __('Concepto') }}</flux:table.column>
            <flux:table.column>{{ __('Medio de pago') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Monto') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->recibos as $recibo)
                <flux:table.row wire:key="recibo-{{ $recibo->id }}">
                    <flux:table.cell>{{ $recibo->fecha->format('d/m/Y') }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:link :href="route('clientes.cta-cte', $recibo->cliente)" wire:navigate>
                            {{ $recibo->cliente->nombre }}
                        </flux:link>
                    </flux:table.cell>
                    <flux:table.cell>{{ $recibo->concepto ?: '—' }}</flux:table.cell>
                    <flux:table.cell>{{ ucfirst($recibo->medio_pago) }}</flux:table.cell>
                    <flux:table.cell align="end" class="font-mono">$ {{ number_format((float) $recibo->monto, 2, ',', '.') }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center text-zinc-500">
                        {{ __('Todavía no hay cobranzas registradas.') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{ $this->recibos->links() }}
</div>
