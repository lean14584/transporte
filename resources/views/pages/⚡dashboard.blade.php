<?php

use App\Models\Cheque;
use App\Models\Cliente;
use App\Models\CuentaBancaria;
use App\Models\Guia;
use App\Models\Recibo;
use Illuminate\Support\Facades\Auth;
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
    public function cobradoHoy(): ?float
    {
        if (Auth::user()->isUsuarioCarga()) {
            return null;
        }

        return (float) Recibo::whereDate('fecha', now()->toDateString())->sum('monto');
    }

    #[Computed]
    public function totalACobrar(): ?float
    {
        if (Auth::user()->isUsuarioCarga()) {
            return null;
        }

        return (float) Cliente::where('saldo_cta_cte', '>', 0)->sum('saldo_cta_cte');
    }

    #[Computed]
    public function saldoBancario(): ?float
    {
        if (! Auth::user()->isAdministrador()) {
            return null;
        }

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
        if (! Auth::user()->isAdministrador()) {
            return collect();
        }

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

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="min-w-0 rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900/50 dark:bg-blue-950/30">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/50">
                    <flux:icon.truck class="size-5 text-blue-600 dark:text-blue-400" />
                </div>
                <flux:text size="sm" class="text-blue-900/70 dark:text-blue-300">{{ __('Guías en tránsito') }}</flux:text>
            </div>
            <flux:heading size="xl" class="mt-3 text-blue-900 dark:text-blue-100">{{ $this->guiasEnTransito }}</flux:heading>
        </div>

        @unless (auth()->user()->isUsuarioCarga())
            <div class="min-w-0 rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/30">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-emerald-100 p-2 dark:bg-emerald-900/50">
                        <flux:icon.banknotes class="size-5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <flux:text size="sm" class="text-emerald-900/70 dark:text-emerald-300">{{ __('Cobrado hoy') }}</flux:text>
                </div>
                <flux:heading size="xl" class="mt-3 font-mono text-emerald-900 dark:text-emerald-100">$ {{ number_format($this->cobradoHoy, 2, ',', '.') }}</flux:heading>
            </div>

            <div class="min-w-0 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/30">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-amber-100 p-2 dark:bg-amber-900/50">
                        <flux:icon.exclamation-triangle class="size-5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <flux:text size="sm" class="text-amber-900/70 dark:text-amber-300">{{ __('Total a cobrar (cta cte)') }}</flux:text>
                </div>
                <flux:heading size="xl" class="mt-3 font-mono text-amber-900 dark:text-amber-100">$ {{ number_format($this->totalACobrar, 2, ',', '.') }}</flux:heading>
            </div>
        @endunless

        @if (auth()->user()->isAdministrador())
            <div class="min-w-0 rounded-xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-900/50 dark:bg-violet-950/30">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-violet-100 p-2 dark:bg-violet-900/50">
                        <flux:icon.building-library class="size-5 text-violet-600 dark:text-violet-400" />
                    </div>
                    <flux:text size="sm" class="text-violet-900/70 dark:text-violet-300">{{ __('Saldo en cuentas bancarias') }}</flux:text>
                </div>
                <flux:heading size="xl" class="mt-3 font-mono text-violet-900 dark:text-violet-100">$ {{ number_format($this->saldoBancario, 2, ',', '.') }}</flux:heading>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="min-w-0 rounded-xl border border-zinc-200 dark:border-white/10 overflow-hidden @unless (auth()->user()->isAdministrador()) lg:col-span-2 @endunless">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-200 p-4 dark:border-white/10">
                <flux:heading size="lg">{{ __('Últimas guías') }}</flux:heading>
                <flux:link :href="route('guias.index')" wire:navigate>{{ __('Ver todas') }}</flux:link>
            </div>

            <div class="hidden overflow-x-auto md:block">
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

            <div class="divide-y divide-zinc-200 md:hidden dark:divide-white/10">
                @forelse ($this->ultimasGuias as $guia)
                    <div class="flex items-center justify-between gap-3 p-4" wire:key="dash-guia-mobile-{{ $guia->id }}">
                        <div>
                            <div class="font-mono text-sm text-zinc-500">{{ $guia->folio() }}</div>
                            <div class="font-medium">{{ $guia->cliente->nombre }}</div>
                        </div>
                        <flux:badge size="sm" :color="$guia->estado === 'recibida' ? 'green' : 'yellow'">
                            {{ $guia->estado === 'recibida' ? __('Recibida') : __('Cargada') }}
                        </flux:badge>
                    </div>
                @empty
                    <div class="p-4 text-center text-zinc-500">{{ __('Todavía no hay guías cargadas.') }}</div>
                @endforelse
            </div>
        </div>

        @if (auth()->user()->isAdministrador())
            <div class="min-w-0 rounded-xl border border-zinc-200 dark:border-white/10 overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-200 p-4 dark:border-white/10">
                    <flux:heading size="lg">{{ __('Próximos cheques a pagar') }}</flux:heading>
                    <flux:link :href="route('cheques.index')" wire:navigate>{{ __('Ver todos') }}</flux:link>
                </div>

                <div class="hidden overflow-x-auto md:block">
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

                <div class="divide-y divide-zinc-200 md:hidden dark:divide-white/10">
                    @forelse ($this->proximosCheques as $cheque)
                        <div class="flex items-center justify-between gap-3 p-4" wire:key="dash-cheque-mobile-{{ $cheque->id }}">
                            <div>
                                <div class="text-sm text-zinc-500">{{ $cheque->fecha_pago->format('d/m/Y') }}</div>
                                <div class="font-medium">{{ $cheque->beneficiario }}</div>
                            </div>
                            <div class="font-mono">$ {{ number_format((float) $cheque->monto, 2, ',', '.') }}</div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-zinc-500">{{ __('No hay cheques pendientes.') }}</div>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</div>
