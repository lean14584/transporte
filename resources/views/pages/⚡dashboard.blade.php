<?php

use App\Models\Cheque;
use App\Models\Cliente;
use App\Models\CuentaBancaria;
use App\Models\Guia;
use App\Models\Recibo;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    #[Computed]
    public function guiasEnTransito(): int
    {
        return Guia::where('estado', 'cargada')->count();
    }

    #[Computed]
    public function cobradoHoy(): float
    {
        return (float) Recibo::whereDate('fecha', now()->toDateString())->sum('monto');
    }

    #[Computed]
    public function totalACobrar(): float
    {
        return (float) Cliente::where('saldo_cta_cte', '>', 0)->sum('saldo_cta_cte');
    }

    #[Computed]
    public function saldoBancario(): float
    {
        return (float) CuentaBancaria::sum('saldo');
    }

    #[Computed]
    public function ultimasGuias()
    {
        return Guia::with(['cliente', 'transporte'])
            ->latest('fecha_carga')
            ->latest('id')
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function proximosCheques()
    {
        return Cheque::where('estado', 'emitido')
            ->orderBy('fecha_pago')
            ->limit(6)
            ->get();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
        <flux:subheading>{{ __('Resumen general de la operación.') }}</flux:subheading>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-white/10">
            <flux:text size="sm">{{ __('Guías en tránsito') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->guiasEnTransito }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 p-4 dark:border-white/10">
            <flux:text size="sm">{{ __('Cobrado hoy') }}</flux:text>
            <flux:heading size="xl" class="mt-1 font-mono">$ {{ number_format($this->cobradoHoy, 2, ',', '.') }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 p-4 dark:border-white/10">
            <flux:text size="sm">{{ __('Total a cobrar (cta cte)') }}</flux:text>
            <flux:heading size="xl" class="mt-1 font-mono text-red-600 dark:text-red-400">$ {{ number_format($this->totalACobrar, 2, ',', '.') }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 p-4 dark:border-white/10">
            <flux:text size="sm">{{ __('Saldo en cuentas bancarias') }}</flux:text>
            <flux:heading size="xl" class="mt-1 font-mono">$ {{ number_format($this->saldoBancario, 2, ',', '.') }}</flux:heading>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 dark:border-white/10 overflow-hidden">
            <div class="flex items-center justify-between border-b border-zinc-200 p-4 dark:border-white/10">
                <flux:heading size="lg">{{ __('Últimas guías') }}</flux:heading>
                <flux:link :href="route('guias.index')" wire:navigate>{{ __('Ver todas') }}</flux:link>
            </div>

            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Folio') }}</flux:table.column>
                        <flux:table.column>{{ __('Cliente') }}</flux:table.column>
                        <flux:table.column>{{ __('Estado') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->ultimasGuias as $guia)
                            <flux:table.row wire:key="dash-guia-{{ $guia->id }}">
                                <flux:table.cell class="font-mono">{{ $guia->folio() }}</flux:table.cell>
                                <flux:table.cell>{{ $guia->cliente->nombre }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$guia->estado === 'recibida' ? 'green' : 'yellow'">
                                        {{ $guia->estado === 'recibida' ? __('Recibida') : __('Cargada') }}
                                    </flux:badge>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="3" class="text-center text-zinc-500">
                                    {{ __('Todavía no hay guías cargadas.') }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-white/10 overflow-hidden">
            <div class="flex items-center justify-between border-b border-zinc-200 p-4 dark:border-white/10">
                <flux:heading size="lg">{{ __('Próximos cheques a pagar') }}</flux:heading>
                <flux:link :href="route('cheques.index')" wire:navigate>{{ __('Ver todos') }}</flux:link>
            </div>

            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Fecha de pago') }}</flux:table.column>
                        <flux:table.column>{{ __('Beneficiario') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Monto') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->proximosCheques as $cheque)
                            <flux:table.row wire:key="dash-cheque-{{ $cheque->id }}">
                                <flux:table.cell>{{ $cheque->fecha_pago->format('d/m/Y') }}</flux:table.cell>
                                <flux:table.cell>{{ $cheque->beneficiario }}</flux:table.cell>
                                <flux:table.cell align="end" class="font-mono">$ {{ number_format((float) $cheque->monto, 2, ',', '.') }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="3" class="text-center text-zinc-500">
                                    {{ __('No hay cheques pendientes.') }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    </div>
</div>
