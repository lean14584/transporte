<?php

use App\Models\CuentaBancaria;
use App\Models\CuentaBancariaMovimiento;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Cuentas bancarias')] class extends Component {
    public ?int $editingId = null;

    public string $banco = '';
    public string $numero_cuenta = '';
    public string $alias_cbu = '';
    public string $saldo = '0';

    public ?int $ajustandoId = null;
    public string $ajuste_tipo = 'ingreso';
    public string $ajuste_monto = '';
    public string $ajuste_motivo = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->isAdministrador(), 403);
    }

    #[Computed]
    public function cuentas()
    {
        return CuentaBancaria::orderBy('banco')->get();
    }

    public function crear(): void
    {
        $this->reset(['editingId', 'banco', 'numero_cuenta', 'alias_cbu']);
        $this->saldo = '0';
        Flux::modal('cuenta-bancaria-form')->show();
    }

    public function editar(int $id): void
    {
        $cuenta = CuentaBancaria::findOrFail($id);

        $this->editingId = $cuenta->id;
        $this->banco = $cuenta->banco;
        $this->numero_cuenta = (string) $cuenta->numero_cuenta;
        $this->alias_cbu = (string) $cuenta->alias_cbu;
        $this->saldo = (string) $cuenta->saldo;

        Flux::modal('cuenta-bancaria-form')->show();
    }

    public function guardar(): void
    {
        $rules = [
            'banco' => ['required', 'string', 'max:255'],
            'numero_cuenta' => ['nullable', 'string', 'max:255'],
            'alias_cbu' => ['nullable', 'string', 'max:255'],
        ];

        if (! $this->editingId) {
            $rules['saldo'] = ['required', 'numeric'];
        }

        $validated = $this->validate($rules);

        if ($this->editingId) {
            CuentaBancaria::findOrFail($this->editingId)->actualizarDatos($validated);
        } else {
            CuentaBancaria::abrir($validated);
        }

        Flux::modal('cuenta-bancaria-form')->close();
        unset($this->cuentas);

        Flux::toast(variant: 'success', text: __('Cuenta bancaria guardada.'));
    }

    public function abrirAjuste(int $id): void
    {
        $this->reset(['ajustandoId', 'ajuste_tipo', 'ajuste_monto', 'ajuste_motivo']);
        $this->ajustandoId = $id;
        $this->ajuste_tipo = 'ingreso';

        Flux::modal('ajustar-saldo')->show();
    }

    public function guardarAjuste(): void
    {
        $validated = $this->validate([
            'ajuste_tipo' => ['required', 'in:ingreso,egreso'],
            'ajuste_monto' => ['required', 'numeric', 'gt:0'],
            'ajuste_motivo' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        $cuenta = CuentaBancaria::findOrFail($this->ajustandoId);

        try {
            CuentaBancariaMovimiento::registrar(
                $cuenta,
                $validated['ajuste_tipo'],
                "Ajuste manual: {$validated['ajuste_motivo']}",
                (float) $validated['ajuste_monto'],
            );
        } catch (\RuntimeException $e) {
            $this->addError('ajuste_monto', $e->getMessage());

            return;
        }

        Flux::modal('ajustar-saldo')->close();
        unset($this->cuentas);

        Flux::toast(variant: 'success', text: __('Ajuste registrado.'));
    }

    public function eliminar(int $id): void
    {
        try {
            CuentaBancaria::whereKey($id)->delete();
        } catch (\Illuminate\Database\QueryException) {
            Flux::toast(variant: 'danger', text: __('No se puede eliminar: la cuenta tiene cheques asociados.'));

            return;
        }

        unset($this->cuentas);

        Flux::toast(variant: 'success', text: __('Cuenta bancaria eliminada.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Cuentas bancarias') }}</flux:heading>
            <flux:subheading>{{ __('Cuentas contra las que se emiten los cheques.') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="crear">
            {{ __('Nueva cuenta') }}
        </flux:button>
    </div>

    <div class="hidden overflow-x-auto md:block">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Banco') }}</flux:table.column>
                <flux:table.column>{{ __('Número de cuenta') }}</flux:table.column>
                <flux:table.column>{{ __('Alias / CBU') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Saldo') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->cuentas as $cuenta)
                    <flux:table.row wire:key="cuenta-{{ $cuenta->id }}">
                        <flux:table.cell class="font-medium">{{ $cuenta->banco }}</flux:table.cell>
                        <flux:table.cell>{{ $cuenta->numero_cuenta ?: '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $cuenta->alias_cbu ?: '—' }}</flux:table.cell>
                        <flux:table.cell align="end" class="font-mono">$ {{ number_format((float) $cuenta->saldo, 2, ',', '.') }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button size="sm" variant="ghost" icon="banknotes" aria-label="{{ __('Movimientos') }}" :href="route('cuentas-bancarias.movimientos', $cuenta)" wire:navigate />
                            <flux:button size="sm" variant="ghost" icon="adjustments-horizontal" aria-label="{{ __('Ajustar saldo') }}" wire:click="abrirAjuste({{ $cuenta->id }})" />
                            <flux:button size="sm" variant="ghost" icon="pencil" aria-label="{{ __('Editar') }}" wire:click="editar({{ $cuenta->id }})" />
                            <flux:button
                                size="sm"
                                variant="ghost"
                                aria-label="{{ __('Eliminar') }}"
                                icon="trash"
                                wire:click="eliminar({{ $cuenta->id }})"
                                wire:confirm="{{ __('¿Eliminar esta cuenta bancaria?') }}"
                            />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-zinc-500">
                            {{ __('Todavía no hay cuentas bancarias cargadas.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div class="grid gap-3 md:hidden">
        @forelse ($this->cuentas as $cuenta)
            <div class="rounded-xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-900/50 dark:bg-violet-950/30" wire:key="cuenta-mobile-{{ $cuenta->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-medium text-violet-900 dark:text-violet-100">{{ $cuenta->banco }}</div>
                        <div class="mt-1 text-sm text-violet-900/70 dark:text-violet-300">{{ $cuenta->numero_cuenta ?: '—' }}</div>
                    </div>
                    <div class="flex shrink-0 gap-1">
                        <flux:button size="sm" variant="ghost" icon="banknotes" aria-label="{{ __('Movimientos') }}" :href="route('cuentas-bancarias.movimientos', $cuenta)" wire:navigate />
                        <flux:button size="sm" variant="ghost" icon="adjustments-horizontal" aria-label="{{ __('Ajustar saldo') }}" wire:click="abrirAjuste({{ $cuenta->id }})" />
                        <flux:button size="sm" variant="ghost" icon="pencil" aria-label="{{ __('Editar') }}" wire:click="editar({{ $cuenta->id }})" />
                        <flux:button
                            size="sm"
                            variant="ghost"
                            aria-label="{{ __('Eliminar') }}"
                            icon="trash"
                            wire:click="eliminar({{ $cuenta->id }})"
                            wire:confirm="{{ __('¿Eliminar esta cuenta bancaria?') }}"
                        />
                    </div>
                </div>
                <div class="mt-3 font-mono text-lg text-violet-900 dark:text-violet-100">$ {{ number_format((float) $cuenta->saldo, 2, ',', '.') }}</div>
            </div>
        @empty
            <div class="rounded-xl border border-zinc-200 p-4 text-center text-zinc-500 dark:border-white/10">
                {{ __('Todavía no hay cuentas bancarias cargadas.') }}
            </div>
        @endforelse
    </div>

    <flux:modal name="cuenta-bancaria-form" class="max-w-xl" @close="$set('editingId', null)">
        <form wire:submit="guardar" class="space-y-6">
            <flux:heading size="lg">
                {{ $editingId ? __('Editar cuenta bancaria') : __('Nueva cuenta bancaria') }}
            </flux:heading>

            <flux:input wire:model="banco" :label="__('Banco')" required autofocus />

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input wire:model="numero_cuenta" :label="__('Número de cuenta')" />
                <flux:input wire:model="alias_cbu" :label="__('Alias / CBU')" />
            </div>

            @if ($editingId)
                <flux:input
                    value="{{ number_format((float) $saldo, 2, ',', '.') }}"
                    :label="__('Saldo actual')"
                    disabled
                    :description="__('El saldo cambia al emitir/rechazar cheques o con un ajuste manual (ver botón Ajustar saldo).')"
                />
            @else
                <flux:input wire:model="saldo" :label="__('Saldo inicial')" type="number" step="0.01" />
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="ajustar-saldo" class="max-w-md" @close="$set('ajustandoId', null)">
        <form wire:submit="guardarAjuste" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Ajustar saldo') }}</flux:heading>
                <flux:subheading>{{ __('Queda registrado en el historial de movimientos con tu usuario y el motivo.') }}</flux:subheading>
            </div>

            <flux:select wire:model="ajuste_tipo" :label="__('Tipo')">
                <flux:select.option value="ingreso">{{ __('Ingreso (suma al saldo)') }}</flux:select.option>
                <flux:select.option value="egreso">{{ __('Egreso (resta del saldo)') }}</flux:select.option>
            </flux:select>

            <flux:input wire:model="ajuste_monto" :label="__('Monto')" type="number" step="0.01" required autofocus />

            <flux:textarea wire:model="ajuste_motivo" :label="__('Motivo')" :description="__('Obligatorio: por qué se hace este ajuste.')" required rows="3" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Registrar ajuste') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
