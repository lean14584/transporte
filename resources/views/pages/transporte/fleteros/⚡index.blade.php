<?php

use App\Models\Fletero;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Fleteros')] class extends Component {
    use WithPagination;

    public string $search = '';

    public ?int $editingId = null;

    public string $numero_fletero = '';
    public string $razon_social = '';
    public string $direccion = '';
    public string $cuit = '';
    public string $dni = '';
    public string $telefono = '';
    public string $contacto = '';
    public string $nacionalidad = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function fleteros()
    {
        return Fletero::query()
            ->when($this->search, fn ($query) => $query->where(fn ($q) => $q
                ->where('razon_social', 'like', "%{$this->search}%")
                ->orWhere('cuit', 'like', "%{$this->search}%")))
            ->orderBy('razon_social')
            ->orderBy('id')
            ->paginate(10);
    }

    public function crear(): void
    {
        $this->reset(['editingId', 'numero_fletero', 'razon_social', 'direccion', 'cuit', 'dni', 'telefono', 'contacto', 'nacionalidad']);
        Flux::modal('fletero-form')->show();
    }

    public function editar(int $id): void
    {
        $fletero = Fletero::findOrFail($id);

        $this->editingId = $fletero->id;
        $this->numero_fletero = (string) $fletero->numero_fletero;
        $this->razon_social = $fletero->razon_social;
        $this->direccion = (string) $fletero->direccion;
        $this->cuit = (string) $fletero->cuit;
        $this->dni = (string) $fletero->dni;
        $this->telefono = (string) $fletero->telefono;
        $this->contacto = (string) $fletero->contacto;
        $this->nacionalidad = (string) $fletero->nacionalidad;

        Flux::modal('fletero-form')->show();
    }

    public function guardar(): void
    {
        $validated = $this->validate([
            'numero_fletero' => ['nullable', 'string', 'max:255'],
            'razon_social' => ['required', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'cuit' => ['nullable', 'string', 'max:255'],
            'dni' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'contacto' => ['nullable', 'string', 'max:255'],
            'nacionalidad' => ['nullable', 'string', 'max:255'],
        ]);

        Fletero::updateOrCreate(['id' => $this->editingId], $validated);

        Flux::modal('fletero-form')->close();
        unset($this->fleteros);

        Flux::toast(variant: 'success', text: __('Fletero guardado.'));
    }

    public function eliminar(int $id): void
    {
        abort_unless(Auth::user()->isAdministrador(), 403);

        try {
            Fletero::whereKey($id)->delete();
        } catch (\Illuminate\Database\QueryException) {
            Flux::toast(variant: 'danger', text: __('No se puede eliminar: el fletero tiene registros asociados.'));

            return;
        }

        unset($this->fleteros);

        Flux::toast(variant: 'success', text: __('Fletero eliminado.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-page-header :title="__('Fleteros')" :subtitle="__('Transportistas independientes contratados por flete.')">
        <flux:button variant="primary" icon="plus" wire:click="crear">
            {{ __('Nuevo fletero') }}
        </flux:button>
    </x-page-header>

    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Buscar por razón social o CUIT...') }}" class="max-w-sm" />

    <div class="hidden overflow-x-auto md:block">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Razón social') }}</flux:table.column>
                <flux:table.column>{{ __('CUIT') }}</flux:table.column>
                <flux:table.column>{{ __('Teléfono') }}</flux:table.column>
                <flux:table.column>{{ __('Contacto') }}</flux:table.column>
                @unless (auth()->user()->isUsuarioCarga())
                    <flux:table.column align="end">{{ __('Saldo cta cte') }}</flux:table.column>
                @endunless
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->fleteros as $fletero)
                    <flux:table.row wire:key="fletero-{{ $fletero->id }}">
                        <flux:table.cell class="font-medium">{{ $fletero->razon_social }}</flux:table.cell>
                        <flux:table.cell>{{ $fletero->cuit ?: '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $fletero->telefono ?: '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $fletero->contacto ?: '—' }}</flux:table.cell>
                        @unless (auth()->user()->isUsuarioCarga())
                            <flux:table.cell align="end" class="font-mono {{ $fletero->saldo_cta_cte > 0 ? 'text-amber-600 dark:text-amber-400' : '' }}">
                                $ {{ number_format((float) $fletero->saldo_cta_cte, 2, ',', '.') }}
                            </flux:table.cell>
                        @endunless
                        <flux:table.cell align="end">
                            @unless (auth()->user()->isUsuarioCarga())
                                <flux:button size="sm" variant="ghost" icon="banknotes" aria-label="{{ __('Cuenta corriente') }}" :href="route('fleteros.cta-cte', $fletero)" wire:navigate />
                            @endunless
                            <flux:button size="sm" variant="ghost" icon="pencil" aria-label="{{ __('Editar') }}" wire:click="editar({{ $fletero->id }})" />
                            @if (auth()->user()->isAdministrador())
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    aria-label="{{ __('Eliminar') }}"
                                    icon="trash"
                                    wire:click="eliminar({{ $fletero->id }})"
                                    wire:confirm="{{ __('¿Eliminar este fletero?') }}"
                                />
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center text-zinc-500">
                            {{ __('Todavía no hay fleteros cargados.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div class="grid gap-3 md:hidden">
        @forelse ($this->fleteros as $fletero)
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-white/10" wire:key="fletero-mobile-{{ $fletero->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-medium">{{ $fletero->razon_social }}</div>
                        <div class="mt-1 text-sm text-zinc-500">{{ __('CUIT') }} {{ $fletero->cuit ?: '—' }}</div>
                    </div>
                    <div class="flex shrink-0 gap-1">
                        @unless (auth()->user()->isUsuarioCarga())
                            <flux:button size="sm" variant="ghost" icon="banknotes" aria-label="{{ __('Cuenta corriente') }}" :href="route('fleteros.cta-cte', $fletero)" wire:navigate />
                        @endunless
                        <flux:button size="sm" variant="ghost" icon="pencil" aria-label="{{ __('Editar') }}" wire:click="editar({{ $fletero->id }})" />
                        @if (auth()->user()->isAdministrador())
                            <flux:button
                                size="sm"
                                variant="ghost"
                                aria-label="{{ __('Eliminar') }}"
                                icon="trash"
                                wire:click="eliminar({{ $fletero->id }})"
                                wire:confirm="{{ __('¿Eliminar este fletero?') }}"
                            />
                        @endif
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-white/5">
                    <span class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-zinc-500">
                        <span>{{ __('Tel.') }} {{ $fletero->telefono ?: '—' }}</span>
                        <span>{{ __('Contacto') }} {{ $fletero->contacto ?: '—' }}</span>
                    </span>
                    @unless (auth()->user()->isUsuarioCarga())
                        <span class="font-mono text-sm {{ $fletero->saldo_cta_cte > 0 ? 'text-amber-600 dark:text-amber-400' : '' }}">
                            $ {{ number_format((float) $fletero->saldo_cta_cte, 2, ',', '.') }}
                        </span>
                    @endunless
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-zinc-200 p-4 text-center text-zinc-500 dark:border-white/10">
                {{ __('Todavía no hay fleteros cargados.') }}
            </div>
        @endforelse
    </div>

    {{ $this->fleteros->links() }}

    <flux:modal name="fletero-form" class="max-w-2xl" @close="$set('editingId', null)">
        <form wire:submit="guardar" class="space-y-6">
            <flux:heading size="lg">
                {{ $editingId ? __('Editar fletero') : __('Nuevo fletero') }}
            </flux:heading>

            <flux:input wire:model="razon_social" :label="__('Razón social')" required autofocus />
            <flux:input wire:model="direccion" :label="__('Dirección')" />

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input wire:model="numero_fletero" :label="__('Número de fletero')" />
                <flux:input wire:model="cuit" :label="__('CUIT')" />
                <flux:input wire:model="dni" :label="__('DNI')" />
                <flux:input wire:model="telefono" :label="__('Teléfono')" />
                <flux:input wire:model="contacto" :label="__('Contacto')" />
                <flux:input wire:model="nacionalidad" :label="__('Nacionalidad')" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="guardar">{{ __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
