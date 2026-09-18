<?php

use App\Models\Cheque;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Cheques')] class extends Component {
    use WithPagination;

    public string $estado = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->isAdministrador(), 403);
    }

    public function updatingEstado(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function cheques()
    {
        return Cheque::query()
            ->with('cuentaBancaria')
            ->when($this->estado, fn ($query) => $query->where('estado', $this->estado))
            ->orderBy('fecha_pago')
            ->orderBy('id')
            ->paginate(10);
    }

    public function marcarCobrado(int $id): void
    {
        try {
            Cheque::cobrar(Cheque::findOrFail($id));
        } catch (\RuntimeException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        unset($this->cheques);
        Flux::toast(variant: 'success', text: __('Cheque marcado como cobrado.'));
    }

    public function marcarRechazado(int $id): void
    {
        try {
            Cheque::rechazar(Cheque::findOrFail($id));
        } catch (\RuntimeException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        unset($this->cheques);
        Flux::toast(variant: 'success', text: __('Cheque marcado como rechazado; el saldo vuelve a la cuenta bancaria.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Cheques') }}</flux:heading>
        <flux:subheading>{{ __('Cheques emitidos contra las cuentas bancarias.') }}</flux:subheading>
    </div>

    <flux:select wire:model.live="estado" class="max-w-xs">
        <flux:select.option value="">{{ __('Todos los estados') }}</flux:select.option>
        <flux:select.option value="emitido">{{ __('Emitido') }}</flux:select.option>
        <flux:select.option value="cobrado">{{ __('Cobrado') }}</flux:select.option>
        <flux:select.option value="rechazado">{{ __('Rechazado') }}</flux:select.option>
    </flux:select>

    <div class="hidden overflow-x-auto md:block">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Número') }}</flux:table.column>
                <flux:table.column>{{ __('Banco') }}</flux:table.column>
                <flux:table.column>{{ __('Beneficiario') }}</flux:table.column>
                <flux:table.column>{{ __('Fecha de pago') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Monto') }}</flux:table.column>
                <flux:table.column>{{ __('Estado') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->cheques as $cheque)
                    <flux:table.row wire:key="cheque-{{ $cheque->id }}">
                        <flux:table.cell class="font-mono">{{ $cheque->numero }}</flux:table.cell>
                        <flux:table.cell>{{ $cheque->banco }}</flux:table.cell>
                        <flux:table.cell>{{ $cheque->beneficiario }}</flux:table.cell>
                        <flux:table.cell>{{ $cheque->fecha_pago->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell align="end" class="font-mono">$ {{ number_format((float) $cheque->monto, 2, ',', '.') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="match ($cheque->estado) {
                                'cobrado' => 'green',
                                'rechazado' => 'red',
                                default => 'yellow',
                            }">
                                {{ ucfirst($cheque->estado) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            @if ($cheque->estado === 'emitido')
                                <flux:button size="sm" variant="ghost" icon="check" wire:click="marcarCobrado({{ $cheque->id }})">
                                    {{ __('Cobrado') }}
                                </flux:button>
                                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="marcarRechazado({{ $cheque->id }})">
                                    {{ __('Rechazado') }}
                                </flux:button>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center text-zinc-500">
                            {{ __('Todavía no hay cheques emitidos.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div class="grid gap-3 md:hidden">
        @forelse ($this->cheques as $cheque)
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-white/10" wire:key="cheque-mobile-{{ $cheque->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-mono text-sm text-zinc-500">{{ $cheque->numero }} · {{ $cheque->banco }}</div>
                        <div class="font-medium">{{ $cheque->beneficiario }}</div>
                    </div>
                    <flux:badge size="sm" :color="match ($cheque->estado) {
                        'cobrado' => 'green',
                        'rechazado' => 'red',
                        default => 'yellow',
                    }">
                        {{ ucfirst($cheque->estado) }}
                    </flux:badge>
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-white/5">
                    <div>
                        <div class="text-sm text-zinc-500">{{ $cheque->fecha_pago->format('d/m/Y') }}</div>
                        <div class="font-mono">$ {{ number_format((float) $cheque->monto, 2, ',', '.') }}</div>
                    </div>
                    @if ($cheque->estado === 'emitido')
                        <div class="flex shrink-0 gap-1">
                            <flux:button size="sm" variant="ghost" icon="check" wire:click="marcarCobrado({{ $cheque->id }})" />
                            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="marcarRechazado({{ $cheque->id }})" />
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-zinc-200 p-4 text-center text-zinc-500 dark:border-white/10">
                {{ __('Todavía no hay cheques emitidos.') }}
            </div>
        @endforelse
    </div>

    {{ $this->cheques->links() }}
</div>
