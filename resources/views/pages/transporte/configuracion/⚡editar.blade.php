<?php

use App\Models\Configuracion;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Configuración')] class extends Component {
    public string $porcentaje_ganancia_kilo = '';
    public string $porcentaje_ganancia_unidad = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->isAdministrador(), 403);

        $configuracion = Configuracion::actual();
        $this->porcentaje_ganancia_kilo = (string) $configuracion->porcentaje_ganancia_kilo;
        $this->porcentaje_ganancia_unidad = (string) $configuracion->porcentaje_ganancia_unidad;
    }

    public function guardar(): void
    {
        $validated = $this->validate([
            'porcentaje_ganancia_kilo' => ['required', 'numeric', 'gte:0'],
            'porcentaje_ganancia_unidad' => ['required', 'numeric', 'gte:0'],
        ]);

        Configuracion::actual()->update($validated);

        Flux::toast(variant: 'success', text: __('Configuración guardada.'));
    }
}; ?>

<div class="flex max-w-2xl flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Configuración') }}</flux:heading>
        <flux:subheading>{{ __('Porcentajes de ganancia usados por defecto al recepcionar una guía.') }}</flux:subheading>
    </div>

    <form wire:submit="guardar">
        <div class="overflow-hidden rounded-2xl border border-blue-200/70 bg-gradient-to-br from-blue-100/70 via-blue-50/40 to-indigo-100/70 shadow-sm dark:border-blue-900/40 dark:from-zinc-900 dark:via-zinc-900 dark:to-indigo-950/60">
            <div class="space-y-6 p-6">
                <div>
                    <flux:heading size="sm" class="uppercase tracking-wide text-blue-700/70 dark:text-blue-400/80">
                        {{ __('Ganancia por defecto') }}
                    </flux:heading>

                    <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Se precargan al abrir la recepción de una guía; siguen siendo editables ese día si hace falta un ajuste puntual.') }}
                    </p>

                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <flux:input
                            wire:model="porcentaje_ganancia_kilo"
                            :label="__('% ganancia por kilo')"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            autofocus
                        />
                        <flux:input
                            wire:model="porcentaje_ganancia_unidad"
                            :label="__('% ganancia por unidad')"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                        />
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-blue-200/70 bg-white/50 px-6 py-4 dark:border-blue-900/40 dark:bg-black/10">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="guardar">{{ __('Guardar') }}</flux:button>
            </div>
        </div>
    </form>
</div>
