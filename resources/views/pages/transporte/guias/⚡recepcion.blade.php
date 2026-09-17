<?php

use App\Models\Guia;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Recepción de guía')] class extends Component {
    public Guia $guia;

    public string $pesaje_bruto = '';
    public string $tara = '';
    public string $porcentaje_ganancia = '10';

    public function mount(Guia $guia): void
    {
        abort_if($guia->estado === 'recibida', 403, __('Esta guía ya fue recepcionada.'));

        $this->guia = $guia;
    }

    #[Computed]
    public function neto(): ?float
    {
        if ($this->pesaje_bruto === '' || $this->tara === '') {
            return null;
        }

        return round((float) $this->pesaje_bruto - (float) $this->tara, 2);
    }

    #[Computed]
    public function montoGanancia(): ?float
    {
        if ($this->neto === null || $this->porcentaje_ganancia === '') {
            return null;
        }

        return round($this->neto * (float) $this->porcentaje_ganancia / 100, 2);
    }

    public function recepcionar(): void
    {
        $validated = $this->validate([
            'pesaje_bruto' => ['required', 'numeric', 'gt:0'],
            'tara' => ['required', 'numeric', 'gte:0', 'lt:pesaje_bruto'],
            'porcentaje_ganancia' => ['required', 'numeric', 'gte:0'],
        ]);

        $this->guia->recepcionar(
            pesajeBruto: (float) $validated['pesaje_bruto'],
            tara: (float) $validated['tara'],
            porcentajeGanancia: (float) $validated['porcentaje_ganancia'],
        );

        Flux::toast(variant: 'success', text: __('Guía recepcionada.'));

        $this->redirect(route('guias.index'), navigate: true);
    }
}; ?>

<div class="flex max-w-2xl flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Recepción de guía :folio', ['folio' => $guia->folio()]) }}</flux:heading>
        <flux:subheading>{{ $guia->cliente->nombre }} · {{ $guia->mercaderia }} · {{ $guia->transportista }}</flux:subheading>
    </div>

    <flux:callout icon="information-circle">
        <flux:callout.text>
            {{ __('Cargado el :fecha para :transporte. Cantidad declarada: :cantidad :unidad.', [
                'fecha' => $guia->fecha_carga->format('d/m/Y'),
                'transporte' => $guia->transporte->nombre,
                'cantidad' => number_format((float) $guia->cantidad, 0, ',', '.'),
                'unidad' => $guia->unidad,
            ]) }}
        </flux:callout.text>
    </flux:callout>

    <form wire:submit="recepcionar" class="space-y-6">
        <div class="grid grid-cols-2 gap-4">
            <flux:input wire:model.live="pesaje_bruto" :label="__('Pesaje bruto (kg)')" type="number" step="0.01" min="0.01" required autofocus />
            <flux:input wire:model.live="tara" :label="__('Tara (kg)')" type="number" step="0.01" min="0" required />
        </div>

        <flux:input wire:model.live="porcentaje_ganancia" :label="__('Porcentaje de ganancia (%)')" type="number" step="0.01" min="0" required />

        <div class="rounded-lg border border-zinc-200 p-4 dark:border-white/10">
            <div class="flex justify-between">
                <flux:text>{{ __('Neto') }}</flux:text>
                <flux:text class="font-mono">{{ $this->neto !== null ? number_format($this->neto, 2, ',', '.').' kg' : '—' }}</flux:text>
            </div>
            <div class="mt-2 flex justify-between">
                <flux:text>{{ __('Ganancia a cargar en cta cte del cliente') }}</flux:text>
                <flux:heading class="font-mono">{{ $this->montoGanancia !== null ? '$ '.number_format($this->montoGanancia, 2, ',', '.') : '—' }}</flux:heading>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <flux:button variant="filled" :href="route('guias.index')" wire:navigate>{{ __('Cancelar') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Confirmar recepción') }}</flux:button>
        </div>
    </form>
</div>
