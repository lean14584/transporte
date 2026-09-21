<?php

use App\Models\Recibo;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Cobranzas')] class extends Component {
    use WithPagination;

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

    #[Computed]
    public function recibos()
    {
        return Recibo::query()
            ->with('cliente')
            ->when($this->desde, fn ($query) => $query->whereDate('fecha', '>=', $this->desde))
            ->when($this->hasta, fn ($query) => $query->whereDate('fecha', '<=', $this->hasta))
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

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-end dark:border-emerald-900/50 dark:bg-emerald-950/30">
            <flux:text size="sm" class="text-emerald-900/70 dark:text-emerald-300">{{ __('Cobrado hoy') }}</flux:text>
            <flux:heading size="lg" class="font-mono text-emerald-900 dark:text-emerald-100">$ {{ number_format($this->totalDelDia, 2, ',', '.') }}</flux:heading>
        </div>
    </div>

    <div class="flex flex-wrap items-end gap-4">
        <flux:input wire:model.live="desde" :label="__('Desde')" type="date" class="max-w-xs" />
        <flux:input wire:model.live="hasta" :label="__('Hasta')" type="date" class="max-w-xs" />
        @if ($desde || $hasta)
            <flux:button variant="ghost" icon="x-mark" wire:click="limpiarFiltro">
                {{ __('Limpiar filtro') }}
            </flux:button>
        @endif
    </div>

    <div class="hidden overflow-x-auto md:block">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Folio') }}</flux:table.column>
                <flux:table.column>{{ __('Fecha') }}</flux:table.column>
                <flux:table.column>{{ __('Cliente') }}</flux:table.column>
                <flux:table.column>{{ __('Concepto') }}</flux:table.column>
                <flux:table.column>{{ __('Medio de pago') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Monto') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->recibos as $recibo)
                    <flux:table.row wire:key="recibo-{{ $recibo->id }}">
                        <flux:table.cell class="font-mono">{{ $recibo->folio() }}</flux:table.cell>
                        <flux:table.cell>{{ $recibo->fecha->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:link :href="route('clientes.cta-cte', $recibo->cliente)" wire:navigate>
                                {{ $recibo->cliente->nombre }}
                            </flux:link>
                        </flux:table.cell>
                        <flux:table.cell>{{ $recibo->concepto ?: '—' }}</flux:table.cell>
                        <flux:table.cell>{{ ucfirst($recibo->medio_pago) }}</flux:table.cell>
                        <flux:table.cell align="end" class="font-mono">$ {{ number_format((float) $recibo->monto, 2, ',', '.') }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button size="sm" variant="ghost" icon="printer" aria-label="{{ __('Imprimir recibo') }}" :href="route('recibos.pdf', $recibo)" target="_blank" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center text-zinc-500">
                            {{ __('Todavía no hay cobranzas registradas.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div class="grid gap-3 md:hidden">
        @forelse ($this->recibos as $recibo)
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-white/10" wire:key="recibo-mobile-{{ $recibo->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-mono text-sm text-zinc-500">{{ $recibo->folio() }}</div>
                        <flux:link :href="route('clientes.cta-cte', $recibo->cliente)" wire:navigate class="font-medium">
                            {{ $recibo->cliente->nombre }}
                        </flux:link>
                    </div>
                    <flux:button size="sm" variant="ghost" icon="printer" aria-label="{{ __('Imprimir recibo') }}" :href="route('recibos.pdf', $recibo)" target="_blank" />
                </div>
                <div class="mt-2 text-sm text-zinc-500">{{ $recibo->fecha->format('d/m/Y') }} · {{ ucfirst($recibo->medio_pago) }}</div>
                <div class="mt-3 flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-white/5">
                    <span class="text-sm text-zinc-500">{{ $recibo->concepto ?: '—' }}</span>
                    <span class="font-mono">$ {{ number_format((float) $recibo->monto, 2, ',', '.') }}</span>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-zinc-200 p-4 text-center text-zinc-500 dark:border-white/10">
                {{ __('Todavía no hay cobranzas registradas.') }}
            </div>
        @endforelse
    </div>

    {{ $this->recibos->links() }}
</div>
