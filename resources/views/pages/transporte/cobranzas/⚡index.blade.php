<?php

use App\Livewire\Concerns\FiltraPorRangoDeFechas;
use App\Models\Recibo;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Cobranzas')] class extends Component {
    use WithPagination;
    use FiltraPorRangoDeFechas;

    public function mount(): void
    {
        abort_if(Auth::user()->isUsuarioCarga(), 403);
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
        return (float) Recibo::whereDate('fecha', now()->toDateString())
            ->where('estado', '!=', 'rechazado')
            ->sum('monto');
    }

    public function marcarRechazado(int $id): void
    {
        try {
            Recibo::rechazarCheque(Recibo::findOrFail($id));
        } catch (\RuntimeException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        unset($this->recibos);
        Flux::toast(variant: 'success', text: __('Cheque marcado como rechazado; la deuda vuelve a la cta cte del cliente.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-page-header :title="__('Cobranzas')" :subtitle="__('Recibos emitidos a clientes. El cobro puntual se registra desde la cuenta corriente de cada cliente.')">
        <div class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-2.5 dark:border-white/10 dark:bg-white/5">
            <div class="rounded-lg bg-emerald-100 p-2 dark:bg-emerald-500/10">
                <flux:icon.banknotes class="size-5 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div>
                <flux:text size="sm">{{ __('Cobrado hoy') }}</flux:text>
                <div class="font-mono text-lg font-semibold">$ {{ number_format($this->totalDelDia, 2, ',', '.') }}</div>
            </div>
        </div>
    </x-page-header>

    <x-filtro-rango-fechas :desde="$desde" :hasta="$hasta" :rango-invertido="$this->rangoFechasInvertido()" />

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
                        <flux:table.cell>
                            {{ ucfirst($recibo->medio_pago) }}
                            @if ($recibo->medio_pago === 'cheque')
                                <flux:badge size="sm" :color="$recibo->estado === 'rechazado' ? 'red' : 'green'">
                                    {{ $recibo->estado === 'rechazado' ? __('Rechazado') : __('Confirmado') }}
                                </flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end" class="font-mono">$ {{ number_format((float) $recibo->monto, 2, ',', '.') }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button size="sm" variant="ghost" icon="printer" aria-label="{{ __('Imprimir recibo') }}" :href="route('recibos.pdf', $recibo)" target="_blank" />
                            @if ($recibo->medio_pago === 'cheque' && $recibo->estado === 'confirmado')
                                <flux:button size="sm" variant="ghost" icon="x-mark" aria-label="{{ __('Marcar cheque rechazado') }}" wire:click="marcarRechazado({{ $recibo->id }})" wire:confirm="{{ __('¿Marcar este cheque como rechazado? La deuda vuelve a la cuenta corriente del cliente.') }}" />
                            @endif
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
                    <div class="flex shrink-0 gap-1">
                        <flux:button size="sm" variant="ghost" icon="printer" aria-label="{{ __('Imprimir recibo') }}" :href="route('recibos.pdf', $recibo)" target="_blank" />
                        @if ($recibo->medio_pago === 'cheque' && $recibo->estado === 'confirmado')
                            <flux:button size="sm" variant="ghost" icon="x-mark" aria-label="{{ __('Marcar cheque rechazado') }}" wire:click="marcarRechazado({{ $recibo->id }})" wire:confirm="{{ __('¿Marcar este cheque como rechazado? La deuda vuelve a la cuenta corriente del cliente.') }}" />
                        @endif
                    </div>
                </div>
                <div class="mt-2 text-sm text-zinc-500">
                    {{ $recibo->fecha->format('d/m/Y') }} · {{ ucfirst($recibo->medio_pago) }}
                    @if ($recibo->medio_pago === 'cheque')
                        <flux:badge size="sm" :color="$recibo->estado === 'rechazado' ? 'red' : 'green'">
                            {{ $recibo->estado === 'rechazado' ? __('Rechazado') : __('Confirmado') }}
                        </flux:badge>
                    @endif
                </div>
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
