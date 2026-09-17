<?php

use App\Models\Guia;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Guías')] class extends Component {
    use WithPagination;

    #[Url]
    public string $estado = '';

    public function updatingEstado(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function guias()
    {
        return Guia::query()
            ->with(['cliente', 'transporte'])
            ->when($this->estado, fn ($query) => $query->where('estado', $this->estado))
            ->latest('fecha_carga')
            ->latest('id')
            ->paginate(10);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Guías') }}</flux:heading>
            <flux:subheading>{{ __('Cargas de mercadería en tránsito.') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" :href="route('guias.crear')" wire:navigate>
            {{ __('Cargar guía') }}
        </flux:button>
    </div>

    <flux:select wire:model.live="estado" class="max-w-xs">
        <flux:select.option value="">{{ __('Todos los estados') }}</flux:select.option>
        <flux:select.option value="cargada">{{ __('Cargada') }}</flux:select.option>
        <flux:select.option value="recibida">{{ __('Recibida') }}</flux:select.option>
    </flux:select>

    <div class="hidden overflow-x-auto md:block">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Nº') }}</flux:table.column>
                <flux:table.column>{{ __('Fecha carga') }}</flux:table.column>
                <flux:table.column>{{ __('Cliente') }}</flux:table.column>
                <flux:table.column>{{ __('Transporte') }}</flux:table.column>
                <flux:table.column>{{ __('Mercadería') }}</flux:table.column>
                <flux:table.column>{{ __('Estado') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Ganancia') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->guias as $guia)
                    <flux:table.row wire:key="guia-{{ $guia->id }}">
                        <flux:table.cell class="font-mono">{{ $guia->folio() }}</flux:table.cell>
                        <flux:table.cell>{{ $guia->fecha_carga->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>{{ $guia->cliente->nombre }}</flux:table.cell>
                        <flux:table.cell>{{ $guia->transporte->nombre }}</flux:table.cell>
                        <flux:table.cell>{{ $guia->mercaderia }} ({{ number_format((float) $guia->cantidad, 0, ',', '.') }} {{ $guia->unidad }})</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$guia->estado === 'recibida' ? 'green' : 'yellow'">
                                {{ $guia->estado === 'recibida' ? __('Recibida') : __('Cargada') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end" class="font-mono">
                            {{ $guia->monto_ganancia !== null ? '$ '.number_format((float) $guia->monto_ganancia, 2, ',', '.') : '—' }}
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            @if ($guia->estado === 'cargada')
                                <flux:button size="sm" variant="ghost" icon="scale" :href="route('guias.recepcion', $guia)" wire:navigate>
                                    {{ __('Recepcionar') }}
                                </flux:button>
                            @endif
                            <flux:button size="sm" variant="ghost" icon="printer" :href="route('guias.pdf', $guia)" target="_blank" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="text-center text-zinc-500">
                            {{ __('Todavía no hay guías cargadas.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div class="grid gap-3 md:hidden">
        @forelse ($this->guias as $guia)
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-white/10" wire:key="guia-mobile-{{ $guia->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-mono text-sm text-zinc-500">{{ $guia->folio() }}</div>
                        <div class="font-medium">{{ $guia->cliente->nombre }}</div>
                    </div>
                    <flux:badge size="sm" :color="$guia->estado === 'recibida' ? 'green' : 'yellow'">
                        {{ $guia->estado === 'recibida' ? __('Recibida') : __('Cargada') }}
                    </flux:badge>
                </div>
                <div class="mt-2 text-sm text-zinc-500">
                    {{ $guia->transporte->nombre }} · {{ $guia->mercaderia }} ({{ number_format((float) $guia->cantidad, 0, ',', '.') }} {{ $guia->unidad }})
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-white/5">
                    <span class="font-mono text-sm">
                        {{ $guia->monto_ganancia !== null ? '$ '.number_format((float) $guia->monto_ganancia, 2, ',', '.') : '—' }}
                    </span>
                    <div class="flex shrink-0 gap-1">
                        @if ($guia->estado === 'cargada')
                            <flux:button size="sm" variant="ghost" icon="scale" :href="route('guias.recepcion', $guia)" wire:navigate>
                                {{ __('Recepcionar') }}
                            </flux:button>
                        @endif
                        <flux:button size="sm" variant="ghost" icon="printer" :href="route('guias.pdf', $guia)" target="_blank" />
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-zinc-200 p-4 text-center text-zinc-500 dark:border-white/10">
                {{ __('Todavía no hay guías cargadas.') }}
            </div>
        @endforelse
    </div>

    {{ $this->guias->links() }}
</div>
