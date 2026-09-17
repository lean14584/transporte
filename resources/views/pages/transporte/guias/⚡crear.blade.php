<?php

use App\Models\Cliente;
use App\Models\Guia;
use App\Models\Transporte;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Cargar guía')] class extends Component {
    public string $buscarTransporte = '';
    public ?int $transporte_id = null;
    public string $transporteSeleccionado = '';

    public string $buscarCliente = '';
    public ?int $cliente_id = null;
    public string $clienteSeleccionado = '';

    public string $transportista = '';
    public string $camion_marca = '';
    public string $camion_modelo = '';
    public string $camion_patente = '';
    public string $camion_color = '';
    public string $mercaderia = '';
    public string $cantidad = '';
    public string $unidad = 'kilos';
    public string $fecha_carga = '';

    public function mount(): void
    {
        $this->fecha_carga = now()->toDateString();
    }

    #[Computed]
    public function transportesSugeridos()
    {
        if ($this->transporte_id || strlen($this->buscarTransporte) < 1) {
            return collect();
        }

        return Transporte::where('nombre', 'like', "%{$this->buscarTransporte}%")->limit(8)->get();
    }

    #[Computed]
    public function clientesSugeridos()
    {
        if ($this->cliente_id || strlen($this->buscarCliente) < 1) {
            return collect();
        }

        return Cliente::where('nombre', 'like', "%{$this->buscarCliente}%")->limit(8)->get();
    }

    public function seleccionarTransporte(int $id): void
    {
        $transporte = Transporte::findOrFail($id);
        $this->transporte_id = $transporte->id;
        $this->transporteSeleccionado = $transporte->nombre;
        $this->buscarTransporte = '';
    }

    public function quitarTransporte(): void
    {
        $this->transporte_id = null;
        $this->transporteSeleccionado = '';
    }

    public function seleccionarCliente(int $id): void
    {
        $cliente = Cliente::findOrFail($id);
        $this->cliente_id = $cliente->id;
        $this->clienteSeleccionado = $cliente->nombre;
        $this->buscarCliente = '';
    }

    public function quitarCliente(): void
    {
        $this->cliente_id = null;
        $this->clienteSeleccionado = '';
    }

    public function guardar(): void
    {
        $validated = $this->validate([
            'transporte_id' => ['required', 'exists:transportes,id'],
            'cliente_id' => ['required', 'exists:clientes,id'],
            'transportista' => ['required', 'string', 'max:255'],
            'camion_marca' => ['nullable', 'string', 'max:255'],
            'camion_modelo' => ['nullable', 'string', 'max:255'],
            'camion_patente' => ['nullable', 'string', 'max:255'],
            'camion_color' => ['nullable', 'string', 'max:255'],
            'mercaderia' => ['required', 'string', 'max:255'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'unidad' => ['required', 'in:kilos,unidades'],
            'fecha_carga' => ['required', 'date'],
        ]);

        $guia = Guia::create([
            ...$validated,
            'estado' => 'cargada',
            'usuario_carga_id' => auth()->id(),
        ]);

        Flux::toast(variant: 'success', text: __('Guía :folio cargada.', ['folio' => $guia->folio()]));

        $this->redirect(route('guias.index'), navigate: true);
    }
}; ?>

<div class="flex max-w-2xl flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Cargar guía') }}</flux:heading>
        <flux:subheading>{{ __('Datos de la carga: transporte, cliente, camión y mercadería.') }}</flux:subheading>
    </div>

    <form wire:submit="guardar" class="space-y-6">
        <div class="relative">
            <flux:label>{{ __('Transporte') }}</flux:label>

            @if ($transporte_id)
                <div class="mt-1 flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 dark:border-white/10">
                    <span>{{ $transporteSeleccionado }}</span>
                    <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="quitarTransporte" />
                </div>
            @else
                <flux:input wire:model.live.debounce.200ms="buscarTransporte" icon="magnifying-glass" placeholder="{{ __('Buscar transporte...') }}" />

                @if ($this->transportesSugeridos->isNotEmpty())
                    <div class="absolute z-10 mt-1 w-full rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-white/10 dark:bg-zinc-800">
                        @foreach ($this->transportesSugeridos as $sugerido)
                            <button
                                type="button"
                                wire:click="seleccionarTransporte({{ $sugerido->id }})"
                                class="block w-full px-3 py-2 text-start text-sm hover:bg-zinc-100 dark:hover:bg-white/10"
                            >
                                {{ $sugerido->nombre }}
                            </button>
                        @endforeach
                    </div>
                @endif
            @endif
            @error('transporte_id') <flux:error>{{ $message }}</flux:error> @enderror
        </div>

        <div class="relative">
            <flux:label>{{ __('Cliente') }}</flux:label>

            @if ($cliente_id)
                <div class="mt-1 flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 dark:border-white/10">
                    <span>{{ $clienteSeleccionado }}</span>
                    <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="quitarCliente" />
                </div>
            @else
                <flux:input wire:model.live.debounce.200ms="buscarCliente" icon="magnifying-glass" placeholder="{{ __('Buscar cliente...') }}" />

                @if ($this->clientesSugeridos->isNotEmpty())
                    <div class="absolute z-10 mt-1 w-full rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-white/10 dark:bg-zinc-800">
                        @foreach ($this->clientesSugeridos as $sugerido)
                            <button
                                type="button"
                                wire:click="seleccionarCliente({{ $sugerido->id }})"
                                class="block w-full px-3 py-2 text-start text-sm hover:bg-zinc-100 dark:hover:bg-white/10"
                            >
                                {{ $sugerido->nombre }}
                            </button>
                        @endforeach
                    </div>
                @endif
            @endif
            @error('cliente_id') <flux:error>{{ $message }}</flux:error> @enderror
        </div>

        <flux:input wire:model="transportista" :label="__('Transportista')" placeholder="{{ __('Nombre de quien maneja el camión') }}" required />

        <div class="grid grid-cols-2 gap-4">
            <flux:input wire:model="camion_marca" :label="__('Marca del camión')" />
            <flux:input wire:model="camion_modelo" :label="__('Modelo')" />
            <flux:input wire:model="camion_patente" :label="__('Patente')" />
            <flux:input wire:model="camion_color" :label="__('Color')" />
        </div>

        <flux:input wire:model="mercaderia" :label="__('Mercadería a transportar')" required />

        <div class="grid grid-cols-2 gap-4">
            <flux:input wire:model="cantidad" :label="__('Cantidad')" type="number" step="0.01" min="0.01" required />

            <flux:select wire:model="unidad" :label="__('Unidad')">
                <flux:select.option value="kilos">{{ __('Kilos') }}</flux:select.option>
                <flux:select.option value="unidades">{{ __('Unidades') }}</flux:select.option>
            </flux:select>
        </div>

        <flux:input wire:model="fecha_carga" :label="__('Fecha de carga')" type="date" required />

        <div class="flex justify-end gap-2">
            <flux:button variant="filled" :href="route('guias.index')" wire:navigate>{{ __('Cancelar') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Cargar guía') }}</flux:button>
        </div>
    </form>
</div>
