<?php

use App\Models\Cliente;
use App\Models\Fletero;
use App\Models\Guia;
use App\Models\Producto;
use App\Models\Transporte;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Cargar guía')] class extends Component {
    public string $buscarTransporte = '';
    public ?int $transporte_id = null;
    public string $transporteSeleccionado = '';
    public string $transporteCuit = '';
    public string $transporteNacionalidad = '';

    public string $buscarCliente = '';
    public ?int $cliente_id = null;
    public string $clienteSeleccionado = '';
    public string $clienteNumero = '';
    public string $clienteRazonSocial = '';

    public string $buscarFletero = '';
    public ?int $fletero_id = null;
    public string $transportista = '';
    public string $transportista_documento = '';

    public string $camion_marca = '';
    public string $camion_modelo = '';
    public string $camion_patente = '';
    public string $camion_color = '';

    public string $buscarProducto = '';
    public ?int $producto_id = null;
    public string $mercaderia = '';
    public string $productoNumero = '';
    public string $productoFormaCarga = '';

    public string $cantidad = '';
    public string $tn_cargadas = '';
    public string $precio_cliente = '';
    public string $precio_fletero = '';
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

    #[Computed]
    public function fleterosSugeridos()
    {
        if ($this->fletero_id || strlen($this->buscarFletero) < 1) {
            return collect();
        }

        return Fletero::where('razon_social', 'like', "%{$this->buscarFletero}%")->limit(8)->get();
    }

    #[Computed]
    public function productosSugeridos()
    {
        if ($this->producto_id || strlen($this->buscarProducto) < 1) {
            return collect();
        }

        return Producto::where('nombre', 'like', "%{$this->buscarProducto}%")->limit(8)->get();
    }

    public function seleccionarTransporte(int $id): void
    {
        $transporte = Transporte::findOrFail($id);
        $this->transporte_id = $transporte->id;
        $this->transporteSeleccionado = $transporte->nombre;
        $this->transporteCuit = (string) $transporte->cuit;
        $this->transporteNacionalidad = (string) $transporte->nacionalidad;
        $this->buscarTransporte = '';
    }

    public function quitarTransporte(): void
    {
        $this->transporte_id = null;
        $this->transporteSeleccionado = '';
        $this->transporteCuit = '';
        $this->transporteNacionalidad = '';
    }

    public function seleccionarCliente(int $id): void
    {
        $cliente = Cliente::findOrFail($id);
        $this->cliente_id = $cliente->id;
        $this->clienteSeleccionado = $cliente->nombre;
        $this->clienteNumero = (string) $cliente->numero_cliente;
        $this->clienteRazonSocial = (string) $cliente->razon_social;
        $this->buscarCliente = '';
    }

    public function quitarCliente(): void
    {
        $this->cliente_id = null;
        $this->clienteSeleccionado = '';
        $this->clienteNumero = '';
        $this->clienteRazonSocial = '';
    }

    public function seleccionarFletero(int $id): void
    {
        $fletero = Fletero::findOrFail($id);
        $this->fletero_id = $fletero->id;
        $this->transportista = $fletero->razon_social;
        $this->transportista_documento = (string) ($fletero->dni ?: $fletero->cuit);
        $this->buscarFletero = '';
    }

    public function quitarFletero(): void
    {
        $this->fletero_id = null;
        $this->transportista = '';
        $this->transportista_documento = '';
    }

    public function seleccionarProducto(int $id): void
    {
        $producto = Producto::findOrFail($id);
        $this->producto_id = $producto->id;
        $this->mercaderia = $producto->nombre;
        $this->productoNumero = (string) $producto->numero_producto;
        $this->productoFormaCarga = (string) $producto->forma_de_carga;
        $this->buscarProducto = '';
    }

    public function quitarProducto(): void
    {
        $this->producto_id = null;
        $this->mercaderia = '';
        $this->productoNumero = '';
        $this->productoFormaCarga = '';
    }

    public function guardar(): void
    {
        $esPorKilos = $this->unidad === 'kilos';

        $validated = $this->validate([
            'transporte_id' => ['required', 'exists:transportes,id'],
            'cliente_id' => ['required', 'exists:clientes,id'],
            'fletero_id' => ['required', 'exists:fleteros,id'],
            'producto_id' => ['required', 'exists:productos,id'],
            'camion_marca' => ['nullable', 'string', 'max:255'],
            'camion_modelo' => ['nullable', 'string', 'max:255'],
            'camion_patente' => ['nullable', 'string', 'max:255'],
            'camion_color' => ['nullable', 'string', 'max:255'],
            'cantidad' => $esPorKilos ? ['nullable'] : ['required', 'numeric', 'gt:0'],
            'tn_cargadas' => $esPorKilos ? ['required', 'numeric', 'gt:0'] : ['nullable'],
            'precio_cliente' => $esPorKilos ? ['required', 'numeric', 'gt:0'] : ['nullable'],
            'precio_fletero' => $esPorKilos ? ['required', 'numeric', 'gt:0'] : ['nullable'],
            'unidad' => ['required', 'in:kilos,unidades'],
            'fecha_carga' => ['required', 'date'],
        ]);

        $guia = Guia::create([
            ...$validated,
            'cantidad' => $esPorKilos ? $validated['tn_cargadas'] : $validated['cantidad'],
            'tn_cargadas' => $esPorKilos ? $validated['tn_cargadas'] : null,
            'precio_cliente' => $esPorKilos ? $validated['precio_cliente'] : null,
            'precio_fletero' => $esPorKilos ? $validated['precio_fletero'] : null,
            'transportista' => $this->transportista,
            'transportista_documento' => $this->transportista_documento ?: null,
            'mercaderia' => $this->mercaderia,
            'estado' => 'cargada',
            'usuario_carga_id' => auth()->id(),
        ]);

        Flux::toast(variant: 'success', text: __('Guía :folio cargada.', ['folio' => $guia->folio()]));

        $this->redirect(route('guias.index'), navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Cargar guía') }}</flux:heading>
        <flux:subheading>{{ __('Datos de la carga: transporte, cliente, camión y mercadería.') }}</flux:subheading>
    </div>

    <form wire:submit="guardar">
        <div class="overflow-hidden rounded-2xl border border-blue-200/70 bg-gradient-to-br from-blue-100/70 via-blue-50/40 to-indigo-100/70 shadow-sm dark:border-blue-900/40 dark:from-zinc-900 dark:via-zinc-900 dark:to-indigo-950/60">
            <div class="space-y-6 p-6">
                <div>
                    <flux:heading size="sm" class="uppercase tracking-wide text-blue-700/70 dark:text-blue-400/80">
                        {{ __('Transporte y cliente') }}
                    </flux:heading>

                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="relative">
                            <flux:label>{{ __('Transporte') }}</flux:label>

                            @if ($transporte_id)
                                <div class="mt-1 flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-3 py-2 dark:border-white/10 dark:bg-white/5">
                                    <div>
                                        <div>{{ $transporteSeleccionado }}</div>
                                        <div class="text-xs text-zinc-500">
                                            {{ __('CUIT') }} {{ $transporteCuit ?: '—' }} · {{ __('Nacionalidad') }} {{ $transporteNacionalidad ?: '—' }}
                                        </div>
                                    </div>
                                    <flux:button size="xs" variant="ghost" icon="x-mark" aria-label="{{ __('Quitar transporte seleccionado') }}" wire:click="quitarTransporte" />
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
                                                <div>{{ $sugerido->nombre }}</div>
                                                <div class="text-xs text-zinc-500">
                                                    {{ __('CUIT') }} {{ $sugerido->cuit ?: '—' }} · {{ __('Nacionalidad') }} {{ $sugerido->nacionalidad ?: '—' }}
                                                </div>
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
                                <div class="mt-1 flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-3 py-2 dark:border-white/10 dark:bg-white/5">
                                    <div>
                                        <div>{{ $clienteSeleccionado }}</div>
                                        <div class="text-xs text-zinc-500">
                                            {{ __('N°') }} {{ $clienteNumero ?: '—' }} · {{ $clienteRazonSocial ?: '—' }}
                                        </div>
                                    </div>
                                    <flux:button size="xs" variant="ghost" icon="x-mark" aria-label="{{ __('Quitar cliente seleccionado') }}" wire:click="quitarCliente" />
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
                                                <div>{{ $sugerido->nombre }}</div>
                                                <div class="text-xs text-zinc-500">
                                                    {{ __('N°') }} {{ $sugerido->numero_cliente ?: '—' }} · {{ $sugerido->razon_social ?: '—' }}
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                            @error('cliente_id') <flux:error>{{ $message }}</flux:error> @enderror
                        </div>
                    </div>
                </div>

                <flux:separator />

                <div>
                    <flux:heading size="sm" class="uppercase tracking-wide text-blue-700/70 dark:text-blue-400/80">
                        {{ __('Transportista y camión') }}
                    </flux:heading>

                    <div class="mt-3 space-y-4">
                        <div class="relative">
                            <flux:label>{{ __('Transportista') }}</flux:label>

                            @if ($fletero_id)
                                <div class="mt-1 flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-3 py-2 dark:border-white/10 dark:bg-white/5">
                                    <div>
                                        <div>{{ $transportista }}</div>
                                        <div class="text-xs text-zinc-500">
                                            {{ __('Documento') }} {{ $transportista_documento ?: '—' }}
                                        </div>
                                    </div>
                                    <flux:button size="xs" variant="ghost" icon="x-mark" aria-label="{{ __('Quitar transportista seleccionado') }}" wire:click="quitarFletero" />
                                </div>
                            @else
                                <flux:input wire:model.live.debounce.200ms="buscarFletero" icon="magnifying-glass" placeholder="{{ __('Buscar fletero...') }}" />

                                @if ($this->fleterosSugeridos->isNotEmpty())
                                    <div class="absolute z-10 mt-1 w-full rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-white/10 dark:bg-zinc-800">
                                        @foreach ($this->fleterosSugeridos as $sugerido)
                                            <button
                                                type="button"
                                                wire:click="seleccionarFletero({{ $sugerido->id }})"
                                                class="block w-full px-3 py-2 text-start text-sm hover:bg-zinc-100 dark:hover:bg-white/10"
                                            >
                                                <div>{{ $sugerido->razon_social }}</div>
                                                <div class="text-xs text-zinc-500">
                                                    {{ __('Documento') }} {{ $sugerido->dni ?: $sugerido->cuit ?: '—' }}
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                            @error('fletero_id') <flux:error>{{ $message }}</flux:error> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <flux:input wire:model="camion_marca" :label="__('Marca')" />
                            <flux:input wire:model="camion_modelo" :label="__('Modelo')" />
                            <flux:input wire:model="camion_patente" :label="__('Patente')" />
                            <flux:input wire:model="camion_color" :label="__('Color')" />
                        </div>
                    </div>
                </div>

                <flux:separator />

                <div>
                    <flux:heading size="sm" class="uppercase tracking-wide text-blue-700/70 dark:text-blue-400/80">
                        {{ __('Mercadería') }}
                    </flux:heading>

                    <div class="mt-3 space-y-4">
                        <div class="relative">
                            <flux:label>{{ __('Mercadería a transportar') }}</flux:label>

                            @if ($producto_id)
                                <div class="mt-1 flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-3 py-2 dark:border-white/10 dark:bg-white/5">
                                    <div>
                                        <div>{{ $mercaderia }}</div>
                                        <div class="text-xs text-zinc-500">
                                            {{ __('N°') }} {{ $productoNumero ?: '—' }} · {{ $productoFormaCarga ?: '—' }}
                                        </div>
                                    </div>
                                    <flux:button size="xs" variant="ghost" icon="x-mark" aria-label="{{ __('Quitar producto seleccionado') }}" wire:click="quitarProducto" />
                                </div>
                            @else
                                <flux:input wire:model.live.debounce.200ms="buscarProducto" icon="magnifying-glass" placeholder="{{ __('Buscar producto...') }}" />

                                @if ($this->productosSugeridos->isNotEmpty())
                                    <div class="absolute z-10 mt-1 w-full rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-white/10 dark:bg-zinc-800">
                                        @foreach ($this->productosSugeridos as $sugerido)
                                            <button
                                                type="button"
                                                wire:click="seleccionarProducto({{ $sugerido->id }})"
                                                class="block w-full px-3 py-2 text-start text-sm hover:bg-zinc-100 dark:hover:bg-white/10"
                                            >
                                                <div>{{ $sugerido->nombre }}</div>
                                                <div class="text-xs text-zinc-500">
                                                    {{ __('N°') }} {{ $sugerido->numero_producto ?: '—' }} · {{ $sugerido->forma_de_carga ?: '—' }}
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                            @error('producto_id') <flux:error>{{ $message }}</flux:error> @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 {{ $unidad === 'kilos' ? 'lg:grid-cols-5' : 'lg:grid-cols-3' }}">
                            <flux:select wire:model.live="unidad" :label="__('Unidad')">
                                <flux:select.option value="kilos">{{ __('Kilos') }}</flux:select.option>
                                <flux:select.option value="unidades">{{ __('Unidades') }}</flux:select.option>
                            </flux:select>

                            @if ($unidad === 'kilos')
                                <flux:input wire:model="tn_cargadas" :label="__('TN Cargadas')" type="number" step="0.01" min="0.01" required />
                                <flux:input wire:model="precio_cliente" :label="__('Precio Cliente')" type="number" step="0.01" min="0.01" required />
                                <flux:input wire:model="precio_fletero" :label="__('Precio Fletero')" type="number" step="0.01" min="0.01" required />
                            @else
                                <flux:input wire:model="cantidad" :label="__('Cantidad')" type="number" step="0.01" min="0.01" required />
                            @endif

                            <flux:input wire:model="fecha_carga" :label="__('Fecha de carga')" type="date" required />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-blue-200/70 bg-white/50 px-6 py-4 dark:border-blue-900/40 dark:bg-black/10">
                <flux:button variant="filled" :href="route('guias.index')" wire:navigate>{{ __('Cancelar') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="guardar">{{ __('Cargar guía') }}</flux:button>
            </div>
        </div>
    </form>
</div>
