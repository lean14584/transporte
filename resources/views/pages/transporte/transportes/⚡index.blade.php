<?php

use App\Models\Transporte;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Transportes')] class extends Component {
    use WithPagination;

    public string $search = '';

    public ?int $editingId = null;

    public string $nombre = '';
    public string $direccion = '';
    public string $telefono = '';
    public string $dueno = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function transportes()
    {
        return Transporte::query()
            ->when($this->search, fn ($query) => $query->where('nombre', 'like', "%{$this->search}%"))
            ->orderBy('nombre')
            ->paginate(10);
    }

    public function crear(): void
    {
        $this->reset(['editingId', 'nombre', 'direccion', 'telefono', 'dueno']);
        Flux::modal('transporte-form')->show();
    }

    public function editar(int $id): void
    {
        $transporte = Transporte::findOrFail($id);

        $this->editingId = $transporte->id;
        $this->nombre = $transporte->nombre;
        $this->direccion = (string) $transporte->direccion;
        $this->telefono = (string) $transporte->telefono;
        $this->dueno = (string) $transporte->dueno;

        Flux::modal('transporte-form')->show();
    }

    public function guardar(): void
    {
        $validated = $this->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'dueno' => ['nullable', 'string', 'max:255'],
        ]);

        Transporte::updateOrCreate(['id' => $this->editingId], $validated);

        Flux::modal('transporte-form')->close();
        unset($this->transportes);

        Flux::toast(variant: 'success', text: __('Transporte guardado.'));
    }

    public function eliminar(int $id): void
    {
        Transporte::whereKey($id)->delete();

        unset($this->transportes);

        Flux::toast(variant: 'success', text: __('Transporte eliminado.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Transportes') }}</flux:heading>
            <flux:subheading>{{ __('Empresas de transporte: propias o subcontratadas para el flete.') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="crear">
            {{ __('Nuevo transporte') }}
        </flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Buscar por nombre...') }}" class="max-w-sm" />

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Nombre') }}</flux:table.column>
            <flux:table.column>{{ __('Dirección') }}</flux:table.column>
            <flux:table.column>{{ __('Teléfono') }}</flux:table.column>
            <flux:table.column>{{ __('Dueño') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->transportes as $transporte)
                <flux:table.row wire:key="transporte-{{ $transporte->id }}">
                    <flux:table.cell class="font-medium">{{ $transporte->nombre }}</flux:table.cell>
                    <flux:table.cell>{{ $transporte->direccion ?: '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $transporte->telefono ?: '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $transporte->dueno ?: '—' }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:button size="sm" variant="ghost" icon="pencil" wire:click="editar({{ $transporte->id }})" />
                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="trash"
                            wire:click="eliminar({{ $transporte->id }})"
                            wire:confirm="{{ __('¿Eliminar este transporte?') }}"
                        />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center text-zinc-500">
                        {{ __('Todavía no hay transportes cargados.') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{ $this->transportes->links() }}

    <flux:modal name="transporte-form" class="max-w-lg" @close="$set('editingId', null)">
        <form wire:submit="guardar" class="space-y-6">
            <flux:heading size="lg">
                {{ $editingId ? __('Editar transporte') : __('Nuevo transporte') }}
            </flux:heading>

            <flux:input wire:model="nombre" :label="__('Nombre')" required autofocus />
            <flux:input wire:model="direccion" :label="__('Dirección')" />
            <flux:input wire:model="telefono" :label="__('Teléfono')" />
            <flux:input wire:model="dueno" :label="__('Dueño')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
