<?php

use App\Models\Producto;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Productos')] class extends Component {
    use WithPagination;

    public string $search = '';

    public ?int $editingId = null;

    public string $numero_producto = '';
    public string $nombre = '';
    public string $forma_de_carga = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function productos()
    {
        return Producto::query()
            ->when($this->search, fn ($query) => $query->where('nombre', 'like', "%{$this->search}%")
                ->orWhere('numero_producto', 'like', "%{$this->search}%"))
            ->orderBy('nombre')
            ->orderBy('id')
            ->paginate(10);
    }

    public function crear(): void
    {
        $this->reset(['editingId', 'numero_producto', 'nombre', 'forma_de_carga']);
        Flux::modal('producto-form')->show();
    }

    public function editar(int $id): void
    {
        $producto = Producto::findOrFail($id);

        $this->editingId = $producto->id;
        $this->numero_producto = (string) $producto->numero_producto;
        $this->nombre = $producto->nombre;
        $this->forma_de_carga = (string) $producto->forma_de_carga;

        Flux::modal('producto-form')->show();
    }

    public function guardar(): void
    {
        $validated = $this->validate([
            'numero_producto' => ['nullable', 'string', 'max:255'],
            'nombre' => ['required', 'string', 'max:255'],
            'forma_de_carga' => ['nullable', 'string', 'max:255'],
        ]);

        Producto::updateOrCreate(['id' => $this->editingId], $validated);

        Flux::modal('producto-form')->close();
        unset($this->productos);

        Flux::toast(variant: 'success', text: __('Producto guardado.'));
    }

    public function eliminar(int $id): void
    {
        abort_unless(Auth::user()->isAdministrador(), 403);

        try {
            Producto::whereKey($id)->delete();
        } catch (\Illuminate\Database\QueryException) {
            Flux::toast(variant: 'danger', text: __('No se puede eliminar: el producto tiene registros asociados.'));

            return;
        }

        unset($this->productos);

        Flux::toast(variant: 'success', text: __('Producto eliminado.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Productos') }}</flux:heading>
            <flux:subheading>{{ __('Mercadería que se transporta en las guías.') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="crear">
            {{ __('Nuevo producto') }}
        </flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Buscar por nombre o número...') }}" class="max-w-sm" />

    <div class="hidden overflow-x-auto md:block">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Número') }}</flux:table.column>
                <flux:table.column>{{ __('Nombre') }}</flux:table.column>
                <flux:table.column>{{ __('Forma de carga') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->productos as $producto)
                    <flux:table.row wire:key="producto-{{ $producto->id }}">
                        <flux:table.cell>{{ $producto->numero_producto ?: '—' }}</flux:table.cell>
                        <flux:table.cell class="font-medium">{{ $producto->nombre }}</flux:table.cell>
                        <flux:table.cell>{{ $producto->forma_de_carga ?: '—' }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button size="sm" variant="ghost" icon="pencil" wire:click="editar({{ $producto->id }})" />
                            @if (auth()->user()->isAdministrador())
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    wire:click="eliminar({{ $producto->id }})"
                                    wire:confirm="{{ __('¿Eliminar este producto?') }}"
                                />
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="text-center text-zinc-500">
                            {{ __('Todavía no hay productos cargados.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div class="grid gap-3 md:hidden">
        @forelse ($this->productos as $producto)
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-white/10" wire:key="producto-mobile-{{ $producto->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-medium">{{ $producto->nombre }}</div>
                        <div class="mt-1 text-sm text-zinc-500">{{ __('Nº') }} {{ $producto->numero_producto ?: '—' }}</div>
                    </div>
                    <div class="flex shrink-0 gap-1">
                        <flux:button size="sm" variant="ghost" icon="pencil" wire:click="editar({{ $producto->id }})" />
                        @if (auth()->user()->isAdministrador())
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="trash"
                                wire:click="eliminar({{ $producto->id }})"
                                wire:confirm="{{ __('¿Eliminar este producto?') }}"
                            />
                        @endif
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm text-zinc-500">
                    <span>{{ __('Forma de carga') }} {{ $producto->forma_de_carga ?: '—' }}</span>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-zinc-200 p-4 text-center text-zinc-500 dark:border-white/10">
                {{ __('Todavía no hay productos cargados.') }}
            </div>
        @endforelse
    </div>

    {{ $this->productos->links() }}

    <flux:modal name="producto-form" class="max-w-lg" @close="$set('editingId', null)">
        <form wire:submit="guardar" class="space-y-6">
            <flux:heading size="lg">
                {{ $editingId ? __('Editar producto') : __('Nuevo producto') }}
            </flux:heading>

            <flux:input wire:model="nombre" :label="__('Nombre')" required autofocus />

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input wire:model="numero_producto" :label="__('Número de producto')" />
                <flux:input wire:model="forma_de_carga" :label="__('Forma de carga')" />
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
