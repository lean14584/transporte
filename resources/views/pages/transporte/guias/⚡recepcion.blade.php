<?php

use App\Models\Guia;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Recepción de guía')] class extends Component {
    #[Locked]
    public Guia $guia;

    public string $tn_descargadas = '';

    public function mount(Guia $guia): void
    {
        abort_if(Auth::user()->isUsuarioCarga(), 403);
        abort_if($guia->estado === 'recibida', 403, __('Esta guía ya fue recepcionada.'));

        $this->guia = $guia;
        $this->tn_descargadas = (string) round((float) $guia->cantidad, 2);
    }

    #[Computed]
    public function perdida(): ?float
    {
        if ($this->tn_descargadas === '' || ! is_numeric($this->tn_descargadas)) {
            return null;
        }

        return round((float) $this->guia->cantidad - (float) $this->tn_descargadas, 2);
    }

    #[Computed]
    public function totalCliente(): ?float
    {
        if (! is_numeric($this->tn_descargadas) || $this->guia->precio_cliente === null) {
            return null;
        }

        return round((float) $this->tn_descargadas * (float) $this->guia->precio_cliente, 2);
    }

    #[Computed]
    public function totalFletero(): ?float
    {
        if (! is_numeric($this->tn_descargadas) || $this->guia->precio_fletero === null) {
            return null;
        }

        return round((float) $this->tn_descargadas * (float) $this->guia->precio_fletero, 2);
    }

    public function recepcionar(): void
    {
        $validated = $this->validate([
            'tn_descargadas' => ['required', 'numeric', 'gt:0', 'lte:'.$this->guia->cantidad],
        ], [
            'tn_descargadas.lte' => __('No se puede descargar más de lo cargado (:cantidad :unidad).', [
                'cantidad' => number_format((float) $this->guia->cantidad, 2, ',', '.'),
                'unidad' => $this->guia->unidadLabel(),
            ]),
        ]);

        if ($this->totalCliente === null || $this->totalFletero === null) {
            Flux::toast(variant: 'danger', text: __('Esta guía no tiene Precio Cliente o Precio Fletero cargado; no se puede calcular el total.'));

            return;
        }

        try {
            $this->guia->recepcionar(
                totalDescargado: (float) $validated['tn_descargadas'],
                totalCliente: $this->totalCliente,
                totalFletero: $this->totalFletero,
            );
        } catch (\RuntimeException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
            $this->redirect(route('guias.index'), navigate: true);

            return;
        }

        Flux::toast(variant: 'success', text: __('Guía recepcionada.'));

        $this->redirect(route('guias.index'), navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-page-header :title="__('Recepción de guía :folio', ['folio' => $guia->folio()])" :subtitle="__('Cargado el :fecha', ['fecha' => $guia->fecha_carga->format('d/m/Y')])" />

    <form wire:submit="recepcionar">
        <div class="overflow-hidden rounded-2xl border border-route-200/70 bg-gradient-to-br from-route-100/70 via-route-50/40 to-route-200/70 shadow-sm dark:border-route-800/40 dark:from-zinc-900 dark:via-zinc-900 dark:to-route-950/60">
            <div class="space-y-6 p-6">
                <div>
                    <flux:heading size="sm" class="uppercase tracking-wide text-route-700/70 dark:text-route-300/80">
                        {{ __('Datos generales') }}
                    </flux:heading>

                    <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Cliente') }}</div>
                            <div class="text-xl font-semibold">{{ $guia->cliente->nombre }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Transporte') }}</div>
                            <div class="text-xl font-semibold">{{ $guia->transporte->nombre }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Chofer') }}</div>
                            <div class="text-xl font-semibold">{{ $guia->transportista ?: '—' }}</div>
                        </div>
                    </div>
                </div>

                <flux:separator />

                <div>
                    <flux:heading size="sm" class="uppercase tracking-wide text-route-700/70 dark:text-route-300/80">
                        {{ __('Datos de la carga') }}
                    </flux:heading>

                    <div class="mt-3 grid grid-cols-2 gap-4 lg:grid-cols-4">
                        <div class="rounded-lg border border-white bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Producto') }}</div>
                            <div class="text-xl font-semibold">{{ $guia->producto?->nombre ?? $guia->mercaderia }}</div>
                        </div>
                        <div class="rounded-lg border border-white bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __(':unidad Cargadas', ['unidad' => $guia->unidadLabel()]) }}</div>
                            <div class="text-xl font-semibold font-mono">{{ number_format((float) $guia->cantidad, 2, ',', '.') }}</div>
                        </div>
                        <div class="rounded-lg border border-white bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Precio Cliente') }}</div>
                            <div class="text-xl font-semibold font-mono">{{ $guia->precio_cliente !== null ? '$ '.number_format((float) $guia->precio_cliente, 2, ',', '.') : '—' }}</div>
                        </div>
                        <div class="rounded-lg border border-white bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Precio Fletero') }}</div>
                            <div class="text-xl font-semibold font-mono">{{ $guia->precio_fletero !== null ? '$ '.number_format((float) $guia->precio_fletero, 2, ',', '.') : '—' }}</div>
                        </div>
                    </div>
                </div>

                <flux:separator />

                <div>
                    <flux:heading size="sm" class="uppercase tracking-wide text-route-700/70 dark:text-route-300/80">
                        {{ __(':unidad Descargadas', ['unidad' => $guia->unidadLabel()]) }}
                    </flux:heading>

                    <div class="mt-3 grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <flux:input
                            wire:model.live="tn_descargadas"
                            :label="__(':unidad Descargadas', ['unidad' => $guia->unidadLabel()])"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                            autofocus
                        />

                        <div class="rounded-lg border border-white bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pérdida') }}</div>
                            <div class="text-2xl font-semibold font-mono {{ ($this->perdida ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : '' }}">
                                {{ $this->perdida !== null ? number_format($this->perdida, 2, ',', '.').' '.$guia->unidadLabel() : '—' }}
                            </div>
                        </div>
                    </div>
                </div>

                <flux:separator />

                <div>
                    <flux:heading size="sm" class="uppercase tracking-wide text-route-700/70 dark:text-route-300/80">
                        {{ __('Resultado facturación') }}
                    </flux:heading>

                    <div class="mt-3 grid grid-cols-1 gap-4 lg:grid-cols-3">
                        <div class="rounded-lg border border-white bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Descargado') }}</div>
                            <div class="text-2xl font-semibold font-mono">{{ is_numeric($tn_descargadas) ? number_format((float) $tn_descargadas, 2, ',', '.').' '.$guia->unidadLabel() : '—' }}</div>
                        </div>
                        <div class="rounded-lg border border-white bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Cliente $') }}</div>
                            <div class="text-2xl font-semibold font-mono">{{ $this->totalCliente !== null ? '$ '.number_format($this->totalCliente, 2, ',', '.') : '—' }}</div>
                        </div>
                        <div class="rounded-lg border border-white bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Flete $') }}</div>
                            <div class="text-2xl font-semibold font-mono">{{ $this->totalFletero !== null ? '$ '.number_format($this->totalFletero, 2, ',', '.') : '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-route-200/70 bg-white/50 px-6 py-4 dark:border-route-800/40 dark:bg-black/10">
                <flux:button variant="filled" :href="route('guias.index')" wire:navigate>{{ __('Cancelar') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="recepcionar">{{ __('Confirmar recepción') }}</flux:button>
            </div>
        </div>
    </form>
</div>
