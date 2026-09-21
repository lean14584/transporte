<?php

use App\Models\Transporte;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Transportes')] class extends Component {
    use WithPagination;

    public string $search = '';

    public ?int $editingId = null;

    public string $numero_transporte = '';
    public string $nombre = '';
    public string $direccion = '';
    public string $telefono = '';
    public string $dueno = '';
    public string $cuit = '';
    public string $nacionalidad = '';

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
            ->orderBy('id')
            ->paginate(10);
    }

    public function crear(): void
    {
        $this->reset(['editingId', 'numero_transporte', 'nombre', 'direccion', 'telefono', 'dueno', 'cuit', 'nacionalidad']);
        Flux::modal('transporte-form')->show();
    }

    public function editar(int $id): void
    {
        $transporte = Transporte::findOrFail($id);

        $this->editingId = $transporte->id;
        $this->numero_transporte = (string) $transporte->numero_transporte;
        $this->nombre = $transporte->nombre;
        $this->direccion = (string) $transporte->direccion;
        $this->telefono = (string) $transporte->telefono;
        $this->dueno = (string) $transporte->dueno;
        $this->cuit = (string) $transporte->cuit;
        $this->nacionalidad = (string) $transporte->nacionalidad;

        Flux::modal('transporte-form')->show();
    }

    public function guardar(): void
    {
        $validated = $this->validate([
            'numero_transporte' => ['nullable', 'string', 'max:255'],
            'nombre' => ['required', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'dueno' => ['nullable', 'string', 'max:255'],
            'cuit' => ['nullable', 'string', 'max:255'],
            'nacionalidad' => ['nullable', 'string', 'max:255'],
        ]);

        Transporte::updateOrCreate(['id' => $this->editingId], $validated);

        Flux::modal('transporte-form')->close();
        unset($this->transportes);

        Flux::toast(variant: 'success', text: __('Transporte guardado.'));
    }

    public function eliminar(int $id): void
    {
        abort_unless(Auth::user()->isAdministrador(), 403);

        try {
            Transporte::whereKey($id)->delete();
        } catch (\Illuminate\Database\QueryException) {
            Flux::toast(variant: 'danger', text: __('No se puede eliminar: el transporte tiene guías u órdenes de pago asociadas.'));

            return;
        }

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

    <div class="hidden overflow-x-auto md:block">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Nombre') }}</flux:table.column>
                <flux:table.column>{{ __('CUIT') }}</flux:table.column>
                <flux:table.column>{{ __('Dirección') }}</flux:table.column>
                <flux:table.column>{{ __('Teléfono') }}</flux:table.column>
                <flux:table.column>{{ __('Dueño') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->transportes as $transporte)
                    <flux:table.row wire:key="transporte-{{ $transporte->id }}">
                        <flux:table.cell class="font-medium">{{ $transporte->nombre }}</flux:table.cell>
                        <flux:table.cell>{{ $transporte->cuit ?: '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $transporte->direccion ?: '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $transporte->telefono ?: '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $transporte->dueno ?: '—' }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button size="sm" variant="ghost" icon="pencil" aria-label="{{ __('Editar') }}" wire:click="editar({{ $transporte->id }})" />
                            @if (auth()->user()->isAdministrador())
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    aria-label="{{ __('Eliminar') }}"
                                    icon="trash"
                                    wire:click="eliminar({{ $transporte->id }})"
                                    wire:confirm="{{ __('¿Eliminar este transporte?') }}"
                                />
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center text-zinc-500">
                            {{ __('Todavía no hay transportes cargados.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div class="grid gap-3 md:hidden">
        @forelse ($this->transportes as $transporte)
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-white/10" wire:key="transporte-mobile-{{ $transporte->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-medium">{{ $transporte->nombre }}</div>
                        <div class="mt-1 text-sm text-zinc-500">{{ $transporte->direccion ?: __('Sin dirección') }}</div>
                    </div>
                    <div class="flex shrink-0 gap-1">
                        <flux:button size="sm" variant="ghost" icon="pencil" aria-label="{{ __('Editar') }}" wire:click="editar({{ $transporte->id }})" />
                        @if (auth()->user()->isAdministrador())
                            <flux:button
                                size="sm"
                                variant="ghost"
                                aria-label="{{ __('Eliminar') }}"
                                icon="trash"
                                wire:click="eliminar({{ $transporte->id }})"
                                wire:confirm="{{ __('¿Eliminar este transporte?') }}"
                            />
                        @endif
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm text-zinc-500">
                    <span>{{ __('Tel.') }} {{ $transporte->telefono ?: '—' }}</span>
                    <span>{{ __('Dueño') }} {{ $transporte->dueno ?: '—' }}</span>
                    <span>{{ __('CUIT') }} {{ $transporte->cuit ?: '—' }}</span>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-zinc-200 p-4 text-center text-zinc-500 dark:border-white/10">
                {{ __('Todavía no hay transportes cargados.') }}
            </div>
        @endforelse
    </div>

    {{ $this->transportes->links() }}

    <flux:modal name="transporte-form" class="max-w-2xl" @close="$set('editingId', null)">
        <form wire:submit="guardar" class="space-y-6">
            <flux:heading size="lg">
                {{ $editingId ? __('Editar transporte') : __('Nuevo transporte') }}
            </flux:heading>

            <flux:input wire:model="nombre" :label="__('Nombre')" required autofocus />
            <flux:input wire:model="direccion" :label="__('Dirección')" />

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input wire:model="numero_transporte" :label="__('Número de transporte')" />
                <flux:input wire:model="cuit" :label="__('CUIT')" />
                <flux:input wire:model="telefono" :label="__('Teléfono')" />
                <flux:input wire:model="dueno" :label="__('Dueño')" />
                <flux:input wire:model="nacionalidad" :label="__('Nacionalidad')" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
