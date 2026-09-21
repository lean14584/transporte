<?php

use App\Models\Configuracion;
use App\Models\Guia;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Recepción de guía')] class extends Component {
    public Guia $guia;

    public string $cantidad_recibida = '';
    public string $porcentaje_ganancia = '';

    public string $tn_descargadas = '';
    public string $total_cliente = '';
    public string $total_fletero = '';
    public string $monto_ganancia = '';

    public function mount(Guia $guia): void
    {
        abort_if($guia->estado === 'recibida', 403, __('Esta guía ya fue recepcionada.'));

        $this->guia = $guia;
        $this->cantidad_recibida = (string) round((float) $guia->cantidad, 2);

        $configuracion = Configuracion::actual();
        $this->porcentaje_ganancia = (string) ($guia->unidad === 'kilos'
            ? $configuracion->porcentaje_ganancia_kilo
            : $configuracion->porcentaje_ganancia_unidad);
    }

    #[Computed]
    public function esPorKilos(): bool
    {
        return $this->guia->unidad === 'kilos';
    }

    #[Computed]
    public function neto(): ?float
    {
        return $this->cantidad_recibida === '' ? null : round((float) $this->cantidad_recibida, 2);
    }

    #[Computed]
    public function montoGanancia(): ?float
    {
        if ($this->neto === null || $this->porcentaje_ganancia === '') {
            return null;
        }

        return round($this->neto * (float) $this->porcentaje_ganancia / 100, 2);
    }

    #[Computed]
    public function perdida(): ?float
    {
        if ($this->guia->tn_cargadas === null || $this->tn_descargadas === '' || ! is_numeric($this->tn_descargadas)) {
            return null;
        }

        return round((float) $this->guia->tn_cargadas - (float) $this->tn_descargadas, 2);
    }

    public function recepcionar(): void
    {
        if ($this->esPorKilos) {
            $validated = $this->validate([
                'tn_descargadas' => ['required', 'numeric', 'gt:0'],
                'total_cliente' => ['required', 'numeric', 'gte:0'],
                'total_fletero' => ['required', 'numeric', 'gte:0'],
                'monto_ganancia' => ['required', 'numeric', 'gte:0'],
            ]);

            try {
                $this->guia->recepcionar(
                    totalDescargado: (float) $validated['tn_descargadas'],
                    totalCliente: (float) $validated['total_cliente'],
                    totalFletero: (float) $validated['total_fletero'],
                    montoGanancia: (float) $validated['monto_ganancia'],
                );
            } catch (\RuntimeException $e) {
                Flux::toast(variant: 'danger', text: $e->getMessage());
                $this->redirect(route('guias.index'), navigate: true);

                return;
            }
        } else {
            $validated = $this->validate([
                'cantidad_recibida' => ['required', 'numeric', 'gt:0'],
                'porcentaje_ganancia' => ['required', 'numeric', 'gte:0'],
            ]);

            try {
                $this->guia->recepcionarPorCantidad(
                    cantidadRecibida: (float) $validated['cantidad_recibida'],
                    porcentajeGanancia: (float) $validated['porcentaje_ganancia'],
                );
            } catch (\RuntimeException $e) {
                Flux::toast(variant: 'danger', text: $e->getMessage());
                $this->redirect(route('guias.index'), navigate: true);

                return;
            }
        }

        Flux::toast(variant: 'success', text: __('Guía recepcionada.'));

        $this->redirect(route('guias.index'), navigate: true);
    }
}; ?>

<div class="flex max-w-2xl flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Recepción de guía :folio', ['folio' => $guia->folio()]) }}</flux:heading>
        <flux:subheading>{{ $guia->cliente->nombre }} · {{ $guia->mercaderia }} · {{ $guia->transportista }}</flux:subheading>
    </div>

    <form wire:submit="recepcionar">
        <div class="overflow-hidden rounded-2xl border border-blue-200/70 bg-gradient-to-br from-blue-100/70 via-blue-50/40 to-indigo-100/70 shadow-sm dark:border-blue-900/40 dark:from-zinc-900 dark:via-zinc-900 dark:to-indigo-950/60">
            <div class="space-y-6 p-6">
                <div>
                    <flux:heading size="sm" class="uppercase tracking-wide text-blue-700/70 dark:text-blue-400/80">
                        {{ __('Datos de la carga') }}
                    </flux:heading>

                    <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Cargado el :fecha para :transporte. Cantidad declarada: :cantidad :unidad.', [
                            'fecha' => $guia->fecha_carga->format('d/m/Y'),
                            'transporte' => $guia->transporte->nombre,
                            'cantidad' => number_format((float) $guia->cantidad, 0, ',', '.'),
                            'unidad' => $guia->unidad,
                        ]) }}
                    </p>
                </div>

                <flux:separator />

                @if ($this->esPorKilos)
                    <div>
                        <flux:heading size="sm" class="uppercase tracking-wide text-blue-700/70 dark:text-blue-400/80">
                            {{ __('Resultado facturación') }}
                        </flux:heading>

                        <div class="mt-3 space-y-4">
                            <flux:input
                                wire:model.live="tn_descargadas"
                                :label="__('TN Descargadas')"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                                autofocus
                            />

                            <div class="rounded-lg border border-white bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                                <div class="flex justify-between">
                                    <flux:text>{{ __('Total cargado (declarado al cargar)') }}</flux:text>
                                    <flux:text class="font-mono">{{ $guia->tn_cargadas !== null ? number_format((float) $guia->tn_cargadas, 2, ',', '.').' '.$guia->unidad : '—' }}</flux:text>
                                </div>
                                <div class="mt-2 flex justify-between">
                                    <flux:text>{{ __('Pérdida') }}</flux:text>
                                    <flux:text class="font-mono {{ ($this->perdida ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : '' }}">
                                        {{ $this->perdida !== null ? number_format($this->perdida, 2, ',', '.').' '.$guia->unidad : '—' }}
                                    </flux:text>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <flux:input wire:model="total_cliente" :label="__('Total cliente')" type="number" step="0.01" min="0" required />
                                <flux:input wire:model="total_fletero" :label="__('Total fletero')" type="number" step="0.01" min="0" required />
                            </div>

                            <flux:input wire:model="monto_ganancia" :label="__('Ganancia a cargar en cta cte del cliente')" type="number" step="0.01" min="0" required />
                        </div>
                    </div>
                @else
                    <div>
                        <flux:heading size="sm" class="uppercase tracking-wide text-blue-700/70 dark:text-blue-400/80">
                            {{ __('Cantidad recibida') }}
                        </flux:heading>

                        <div class="mt-3 space-y-4">
                            <flux:input
                                wire:model.live="cantidad_recibida"
                                :label="__('Unidades recibidas')"
                                :description="__('Precargado con lo declarado; ajustalo si llegó de menos.')"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                                autofocus
                            />

                            <flux:input wire:model.live="porcentaje_ganancia" :label="__('Porcentaje de ganancia (%)')" type="number" step="0.01" min="0" required />
                        </div>
                    </div>

                    <flux:separator />

                    <div>
                        <flux:heading size="sm" class="uppercase tracking-wide text-blue-700/70 dark:text-blue-400/80">
                            {{ __('Resultado') }}
                        </flux:heading>

                        <div class="mt-3 rounded-lg border border-white bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="flex justify-between">
                                <flux:text>{{ __('Neto') }}</flux:text>
                                <flux:text class="font-mono">{{ $this->neto !== null ? number_format($this->neto, 2, ',', '.').' '.$guia->unidad : '—' }}</flux:text>
                            </div>
                            <div class="mt-2 flex justify-between">
                                <flux:text>{{ __('Ganancia a cargar en cta cte del cliente') }}</flux:text>
                                <flux:heading class="font-mono">{{ $this->montoGanancia !== null ? '$ '.number_format($this->montoGanancia, 2, ',', '.') : '—' }}</flux:heading>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex justify-end gap-2 border-t border-blue-200/70 bg-white/50 px-6 py-4 dark:border-blue-900/40 dark:bg-black/10">
                <flux:button variant="filled" :href="route('guias.index')" wire:navigate>{{ __('Cancelar') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="recepcionar">{{ __('Confirmar recepción') }}</flux:button>
            </div>
        </div>
    </form>
</div>
