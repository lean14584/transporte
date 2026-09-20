<?php

use App\Models\Cliente;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Clientes')] class extends Component {
    use WithPagination;

    public string $search = '';

    public ?int $editingId = null;

    public string $numero_cliente = '';
    public string $nombre = '';
    public string $razon_social = '';
    public string $direccion = '';
    public string $telefono = '';
    public string $dni = '';
    public string $cuit = '';
    public string $contacto = '';
    public string $nacionalidad = '';
    public string $condicion_iva = 'consumidor_final';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function clientes()
    {
        return Cliente::query()
            ->when($this->search, fn ($query) => $query->where('nombre', 'like', "%{$this->search}%")
                ->orWhere('dni', 'like', "%{$this->search}%"))
            ->orderBy('nombre')
            ->orderBy('id')
            ->paginate(10);
    }

    public function crear(): void
    {
        $this->reset(['editingId', 'numero_cliente', 'nombre', 'razon_social', 'direccion', 'telefono', 'dni', 'cuit', 'contacto', 'nacionalidad']);
        $this->condicion_iva = 'consumidor_final';
        Flux::modal('cliente-form')->show();
    }

    public function editar(int $id): void
    {
        $cliente = Cliente::findOrFail($id);

        $this->editingId = $cliente->id;
        $this->numero_cliente = (string) $cliente->numero_cliente;
        $this->nombre = $cliente->nombre;
        $this->razon_social = (string) $cliente->razon_social;
        $this->direccion = (string) $cliente->direccion;
        $this->telefono = (string) $cliente->telefono;
        $this->dni = (string) $cliente->dni;
        $this->cuit = (string) $cliente->cuit;
        $this->contacto = (string) $cliente->contacto;
        $this->nacionalidad = (string) $cliente->nacionalidad;
        $this->condicion_iva = $cliente->condicion_iva;

        Flux::modal('cliente-form')->show();
    }

    public function guardar(): void
    {
        $validated = $this->validate([
            'numero_cliente' => ['nullable', 'string', 'max:255'],
            'nombre' => ['required', 'string', 'max:255'],
            'razon_social' => ['nullable', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'dni' => ['nullable', 'string', 'max:255'],
            'cuit' => ['nullable', 'string', 'max:255'],
            'contacto' => ['nullable', 'string', 'max:255'],
            'nacionalidad' => ['nullable', 'string', 'max:255'],
            'condicion_iva' => ['required', 'in:responsable_inscripto,monotributista,exento,consumidor_final'],
        ]);

        Cliente::updateOrCreate(['id' => $this->editingId], $validated);

        Flux::modal('cliente-form')->close();
        unset($this->clientes);

        Flux::toast(variant: 'success', text: __('Cliente guardado.'));
    }

    public function eliminar(int $id): void
    {
        abort_unless(Auth::user()->isAdministrador(), 403);

        try {
            Cliente::whereKey($id)->delete();
        } catch (\Illuminate\Database\QueryException) {
            Flux::toast(variant: 'danger', text: __('No se puede eliminar: el cliente tiene guías o movimientos asociados.'));

            return;
        }

        unset($this->clientes);

        Flux::toast(variant: 'success', text: __('Cliente eliminado.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Clientes') }}</flux:heading>
            <flux:subheading>{{ __('Clientes que contratan el flete, con su cuenta corriente.') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="crear">
            {{ __('Nuevo cliente') }}
        </flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Buscar por nombre o DNI...') }}" class="max-w-sm" />

    <div class="hidden overflow-x-auto md:block">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Nombre') }}</flux:table.column>
                <flux:table.column>{{ __('Razón social') }}</flux:table.column>
                <flux:table.column>{{ __('CUIT') }}</flux:table.column>
                <flux:table.column>{{ __('Condición IVA') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Saldo cta cte') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->clientes as $cliente)
                    <flux:table.row wire:key="cliente-{{ $cliente->id }}">
                        <flux:table.cell class="font-medium">{{ $cliente->nombre }}</flux:table.cell>
                        <flux:table.cell>{{ $cliente->razon_social ?: '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $cliente->cuit ?: '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm">{{ str($cliente->condicion_iva)->headline() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end" class="font-mono {{ $cliente->saldo_cta_cte > 0 ? 'text-red-600 dark:text-red-400' : ($cliente->saldo_cta_cte < 0 ? 'text-green-600 dark:text-green-400' : '') }}">
                            $ {{ number_format((float) $cliente->saldo_cta_cte, 2, ',', '.') }}
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button size="sm" variant="ghost" icon="banknotes" :href="route('clientes.cta-cte', $cliente)" wire:navigate />
                            <flux:button size="sm" variant="ghost" icon="pencil" wire:click="editar({{ $cliente->id }})" />
                            @if (auth()->user()->isAdministrador())
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    wire:click="eliminar({{ $cliente->id }})"
                                    wire:confirm="{{ __('¿Eliminar este cliente?') }}"
                                />
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center text-zinc-500">
                            {{ __('Todavía no hay clientes cargados.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div class="grid gap-3 md:hidden">
        @forelse ($this->clientes as $cliente)
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-white/10" wire:key="cliente-mobile-{{ $cliente->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-medium">{{ $cliente->nombre }}</div>
                        <div class="mt-1 text-sm text-zinc-500">{{ $cliente->razon_social ?: '—' }}</div>
                    </div>
                    <flux:badge size="sm">{{ str($cliente->condicion_iva)->headline() }}</flux:badge>
                </div>
                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-zinc-500">
                    <span>{{ __('DNI') }} {{ $cliente->dni ?: '—' }}</span>
                    <span>{{ __('CUIT') }} {{ $cliente->cuit ?: '—' }}</span>
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-white/5">
                    <span
                        class="font-mono text-sm {{ $cliente->saldo_cta_cte > 0 ? 'text-red-600 dark:text-red-400' : ($cliente->saldo_cta_cte < 0 ? 'text-green-600 dark:text-green-400' : '') }}"
                    >
                        $ {{ number_format((float) $cliente->saldo_cta_cte, 2, ',', '.') }}
                    </span>
                    <div class="flex shrink-0 gap-1">
                        <flux:button size="sm" variant="ghost" icon="banknotes" :href="route('clientes.cta-cte', $cliente)" wire:navigate />
                        <flux:button size="sm" variant="ghost" icon="pencil" wire:click="editar({{ $cliente->id }})" />
                        @if (auth()->user()->isAdministrador())
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="trash"
                                wire:click="eliminar({{ $cliente->id }})"
                                wire:confirm="{{ __('¿Eliminar este cliente?') }}"
                            />
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-zinc-200 p-4 text-center text-zinc-500 dark:border-white/10">
                {{ __('Todavía no hay clientes cargados.') }}
            </div>
        @endforelse
    </div>

    {{ $this->clientes->links() }}

    <flux:modal name="cliente-form" class="max-w-3xl" @close="$set('editingId', null)">
        <form wire:submit="guardar" class="space-y-5">
            <flux:heading size="lg">
                {{ $editingId ? __('Editar cliente') : __('Nuevo cliente') }}
            </flux:heading>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input wire:model="nombre" :label="__('Nombre')" required autofocus />
                <flux:input wire:model="razon_social" :label="__('Razón social')" />
            </div>

            <flux:input wire:model="direccion" :label="__('Dirección')" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <flux:input wire:model="numero_cliente" :label="__('Número de cliente')" />
                <flux:input wire:model="dni" :label="__('DNI')" />
                <flux:input wire:model="cuit" :label="__('CUIT')" />
                <flux:input wire:model="telefono" :label="__('Teléfono')" />
                <flux:input wire:model="contacto" :label="__('Contacto')" />
                <flux:input wire:model="nacionalidad" :label="__('Nacionalidad')" />
            </div>

            <flux:select wire:model="condicion_iva" :label="__('Condición IVA')">
                <flux:select.option value="responsable_inscripto">{{ __('Responsable Inscripto') }}</flux:select.option>
                <flux:select.option value="monotributista">{{ __('Monotributista') }}</flux:select.option>
                <flux:select.option value="exento">{{ __('Exento') }}</flux:select.option>
                <flux:select.option value="consumidor_final">{{ __('Consumidor Final') }}</flux:select.option>
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
