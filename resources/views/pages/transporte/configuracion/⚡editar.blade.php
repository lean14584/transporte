<?php

use App\Models\Configuracion;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Configuración')] class extends Component {
    use WithFileUploads;

    public string $empresa_nombre = '';
    public string $empresa_propietario = '';
    public string $empresa_direccion = '';

    public $logo = null;

    public function mount(): void
    {
        abort_unless(Auth::user()->isAdministrador(), 403);

        $configuracion = Configuracion::actual();
        $this->empresa_nombre = (string) $configuracion->empresa_nombre;
        $this->empresa_propietario = (string) $configuracion->empresa_propietario;
        $this->empresa_direccion = (string) $configuracion->empresa_direccion;
    }

    public function guardar(): void
    {
        $validated = $this->validate([
            'empresa_nombre' => ['nullable', 'string', 'max:255'],
            'empresa_propietario' => ['nullable', 'string', 'max:255'],
            'empresa_direccion' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $configuracion = Configuracion::actual();

        if ($this->logo) {
            if ($configuracion->logo_path) {
                Storage::disk('public')->delete($configuracion->logo_path);
            }

            $validated['logo_path'] = $this->logo->store('logos', 'public');
        }

        unset($validated['logo']);

        $configuracion->update($validated);
        $this->logo = null;

        Flux::toast(variant: 'success', text: __('Configuración guardada.'));
    }

    public function quitarLogo(): void
    {
        $configuracion = Configuracion::actual();

        if ($configuracion->logo_path) {
            Storage::disk('public')->delete($configuracion->logo_path);
            $configuracion->update(['logo_path' => null]);
        }

        Flux::toast(variant: 'success', text: __('Logo quitado.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Configuración') }}</flux:heading>
        <flux:subheading>{{ __('Datos de la empresa.') }}</flux:subheading>
    </div>

    <form wire:submit="guardar" class="flex flex-col gap-6">
        <div class="overflow-hidden rounded-2xl border border-indigo-200/70 bg-gradient-to-br from-indigo-100/70 via-blue-50/40 to-cyan-100/70 shadow-sm dark:border-indigo-900/40 dark:from-zinc-900 dark:via-zinc-900 dark:to-indigo-950/60">
            <div class="space-y-6 p-6">
                <div>
                    <flux:heading size="sm" class="uppercase tracking-wide text-indigo-700/70 dark:text-indigo-400/80">
                        {{ __('Datos de la empresa') }}
                    </flux:heading>

                    <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Aparecen en el menú del sistema y en las guías, recibos y órdenes de pago impresas.') }}
                    </p>

                    <div class="mt-4 flex flex-col gap-4">
                        <div class="flex items-center gap-4">
                            <div class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-white bg-white/70 dark:border-white/10 dark:bg-white/5">
                                @if ($logo)
                                    <img src="{{ $logo->temporaryUrl() }}" alt="{{ __('Logo nuevo') }}" class="size-full object-contain" />
                                @elseif (Configuracion::actual()->logoUrl())
                                    <img src="{{ Configuracion::actual()->logoUrl() }}" alt="{{ __('Logo') }}" class="size-full object-contain" />
                                @else
                                    <flux:icon name="truck" class="size-8 text-indigo-400" />
                                @endif
                            </div>

                            <div class="flex flex-col gap-2">
                                <label class="w-fit cursor-pointer rounded-lg border border-indigo-300 bg-white/80 px-3 py-1.5 text-sm font-medium text-indigo-700 hover:bg-white dark:border-indigo-800 dark:bg-white/5 dark:text-indigo-300 dark:hover:bg-white/10">
                                    {{ __('Subir logo') }}
                                    <input type="file" wire:model="logo" accept="image/*" class="hidden" />
                                </label>

                                @if (Configuracion::actual()->logoUrl() && ! $logo)
                                    <flux:button size="xs" variant="ghost" wire:click="quitarLogo" wire:confirm="{{ __('¿Quitar el logo actual?') }}">
                                        {{ __('Quitar logo') }}
                                    </flux:button>
                                @endif

                                @error('logo') <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text> @enderror
                            </div>
                        </div>

                        <flux:input wire:model="empresa_nombre" :label="__('Nombre de la empresa')" />
                        <flux:input wire:model="empresa_propietario" :label="__('Dueño')" />
                        <flux:input wire:model="empresa_direccion" :label="__('Dirección')" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-indigo-200/70 bg-white/50 px-6 py-4 dark:border-indigo-900/40 dark:bg-black/10">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="guardar,logo">{{ __('Guardar') }}</flux:button>
            </div>
        </div>
    </form>
</div>
