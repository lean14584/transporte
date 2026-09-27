<?php

use App\Livewire\Concerns\FiltraPorRangoDeFechas;
use App\Models\OrdenPago;
use App\Models\Recibo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Caja')] class extends Component {
    use WithPagination;
    use FiltraPorRangoDeFechas;

    public function mount(): void
    {
        abort_if(Auth::user()->isUsuarioCarga(), 403);
    }

    /**
     * Cobranzas (Recibo) y Órdenes de pago (OrdenPago) combinadas en un solo
     * libro, ordenadas por fecha. No hay una tabla propia de "movimientos de
     * caja": esto es una lectura combinada de las dos fuentes que ya existen.
     */
    #[Computed]
    public function movimientos()
    {
        $cobros = DB::table('recibos as r')
            ->join('clientes as c', 'c.id', '=', 'r.cliente_id')
            ->where('r.estado', '!=', 'rechazado')
            ->when($this->desde, fn ($query) => $query->whereDate('r.fecha', '>=', $this->desde))
            ->when($this->hasta, fn ($query) => $query->whereDate('r.fecha', '<=', $this->hasta))
            ->selectRaw("r.id, r.fecha, r.monto, r.concepto, 'cobro' as tipo, c.nombre as referencia");

        $pagos = DB::table('orden_pagos as o')
            ->join('fleteros as f', 'f.id', '=', 'o.fletero_id')
            ->leftJoin('cheques as ch', 'ch.id', '=', 'o.cheque_id')
            ->where(function ($query) {
                $query->whereNull('ch.id')->orWhere('ch.estado', '!=', 'rechazado');
            })
            ->when($this->desde, fn ($query) => $query->whereDate('o.fecha', '>=', $this->desde))
            ->when($this->hasta, fn ($query) => $query->whereDate('o.fecha', '<=', $this->hasta))
            ->selectRaw("o.id, o.fecha, o.monto, o.concepto, 'pago' as tipo, f.razon_social as referencia");

        return $cobros->unionAll($pagos)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(20);
    }

    #[Computed]
    public function totalDebe(): float
    {
        return (float) Recibo::query()
            ->where('estado', '!=', 'rechazado')
            ->when($this->desde, fn ($query) => $query->whereDate('fecha', '>=', $this->desde))
            ->when($this->hasta, fn ($query) => $query->whereDate('fecha', '<=', $this->hasta))
            ->sum('monto');
    }

    #[Computed]
    public function totalHaber(): float
    {
        return (float) OrdenPago::query()
            ->whereDoesntHave('cheque', fn ($query) => $query->where('estado', 'rechazado'))
            ->when($this->desde, fn ($query) => $query->whereDate('fecha', '>=', $this->desde))
            ->when($this->hasta, fn ($query) => $query->whereDate('fecha', '<=', $this->hasta))
            ->sum('monto');
    }

    #[Computed]
    public function saldo(): float
    {
        return $this->totalDebe - $this->totalHaber;
    }

    public function folio(object $movimiento): string
    {
        $prefijo = $movimiento->tipo === 'cobro' ? 'R-' : 'OP-';

        return $prefijo.str_pad((string) $movimiento->id, 6, '0', STR_PAD_LEFT);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Caja') }}</flux:heading>
            <flux:subheading>{{ __('Cobranzas y órdenes de pago juntas, como debe y haber.') }}</flux:subheading>
        </div>
    </div>

    <x-filtro-rango-fechas :desde="$desde" :hasta="$hasta" :rango-invertido="$this->rangoFechasInvertido()" />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/30">
            <flux:text size="sm" class="text-emerald-900/70 dark:text-emerald-300">{{ __('Debe (cobranzas)') }}</flux:text>
            <flux:heading size="xl" class="mt-1 font-mono text-emerald-900 dark:text-emerald-100">
                $ {{ number_format($this->totalDebe, 2, ',', '.') }}
            </flux:heading>
        </div>
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-red-950/30">
            <flux:text size="sm" class="text-red-900/70 dark:text-red-300">{{ __('Haber (órdenes de pago)') }}</flux:text>
            <flux:heading size="xl" class="mt-1 font-mono text-red-900 dark:text-red-100">
                $ {{ number_format($this->totalHaber, 2, ',', '.') }}
            </flux:heading>
        </div>
        @php $saldoNegativo = $this->saldo < 0; @endphp
        <div class="rounded-xl border p-4 {{ $saldoNegativo ? 'border-red-200 bg-red-50 dark:border-red-900/50 dark:bg-red-950/30' : 'border-zinc-200 dark:border-white/10' }}">
            <flux:text size="sm" class="{{ $saldoNegativo ? 'text-red-900/70 dark:text-red-300' : '' }}">{{ __('Saldo') }}</flux:text>
            <flux:heading size="xl" class="mt-1 font-mono {{ $saldoNegativo ? 'text-red-900 dark:text-red-100' : '' }}">
                $ {{ number_format($this->saldo, 2, ',', '.') }}
            </flux:heading>
        </div>
    </div>

    <div class="hidden overflow-x-auto md:block">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Folio') }}</flux:table.column>
                <flux:table.column>{{ __('Fecha') }}</flux:table.column>
                <flux:table.column>{{ __('Referencia') }}</flux:table.column>
                <flux:table.column>{{ __('Concepto') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Debe') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Haber') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->movimientos as $movimiento)
                    <flux:table.row wire:key="movimiento-{{ $movimiento->tipo }}-{{ $movimiento->id }}">
                        <flux:table.cell class="font-mono">{{ $this->folio($movimiento) }}</flux:table.cell>
                        <flux:table.cell>{{ \Illuminate\Support\Carbon::parse($movimiento->fecha)->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell class="font-medium">{{ $movimiento->referencia }}</flux:table.cell>
                        <flux:table.cell>{{ $movimiento->concepto ?: '—' }}</flux:table.cell>
                        <flux:table.cell align="end" class="font-mono text-emerald-700 dark:text-emerald-400">
                            {{ $movimiento->tipo === 'cobro' ? '$ '.number_format((float) $movimiento->monto, 2, ',', '.') : '—' }}
                        </flux:table.cell>
                        <flux:table.cell align="end" class="font-mono text-red-700 dark:text-red-400">
                            {{ $movimiento->tipo === 'pago' ? '$ '.number_format((float) $movimiento->monto, 2, ',', '.') : '—' }}
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center text-zinc-500">
                            {{ __('Todavía no hay movimientos de caja.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div class="grid gap-3 md:hidden">
        @forelse ($this->movimientos as $movimiento)
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-white/10" wire:key="movimiento-mobile-{{ $movimiento->tipo }}-{{ $movimiento->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-mono text-sm text-zinc-500">{{ $this->folio($movimiento) }}</div>
                        <div class="font-medium">{{ $movimiento->referencia }}</div>
                    </div>
                    <flux:badge size="sm" :color="$movimiento->tipo === 'cobro' ? 'green' : 'red'">
                        {{ $movimiento->tipo === 'cobro' ? __('Debe') : __('Haber') }}
                    </flux:badge>
                </div>
                <div class="mt-2 text-sm text-zinc-500">
                    {{ \Illuminate\Support\Carbon::parse($movimiento->fecha)->format('d/m/Y') }} · {{ $movimiento->concepto ?: '—' }}
                </div>
                <div class="mt-3 flex items-center justify-end border-t border-zinc-100 pt-3 text-sm dark:border-white/5">
                    <span class="font-mono {{ $movimiento->tipo === 'cobro' ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400' }}">
                        $ {{ number_format((float) $movimiento->monto, 2, ',', '.') }}
                    </span>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-zinc-200 p-4 text-center text-zinc-500 dark:border-white/10">
                {{ __('Todavía no hay movimientos de caja.') }}
            </div>
        @endforelse
    </div>

    {{ $this->movimientos->links() }}
</div>
