<?php

use App\Models\Cheque;
use App\Models\CuentaBancaria;
use App\Models\OrdenPago;
use App\Models\Transporte;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Órdenes de pago')] class extends Component {
    use WithPagination;

    public function mount(): void
    {
        abort_unless(Auth::user()->isAdministrador(), 403);
    }

    public string $buscarTransporte = '';
    public ?int $transporte_id = null;
    public string $transporteSeleccionado = '';

    public string $monto = '';
    public string $concepto = '';
    public string $medio_pago = 'efectivo';

    public ?int $cuenta_bancaria_id = null;
    public string $numero_cheque = '';
    public string $banco_cheque = '';
    public string $fecha_pago_cheque = '';

    #[Computed]
    public function ordenesPago()
    {
        return OrdenPago::query()
            ->with(['transporte', 'cheque'])
            ->latest('fecha')
            ->latest('id')
            ->paginate(10);
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
    public function cuentasBancarias()
    {
        return CuentaBancaria::orderBy('banco')->get();
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

    public function emitir(): void
    {
        $rules = [
            'transporte_id' => ['required', 'exists:transportes,id'],
            'monto' => ['required', 'numeric', 'gt:0'],
            'concepto' => ['nullable', 'string', 'max:255'],
            'medio_pago' => ['required', 'in:efectivo,transferencia,cheque'],
        ];

        if ($this->medio_pago === 'cheque') {
            $rules['cuenta_bancaria_id'] = ['required', 'exists:cuenta_bancarias,id'];
            $rules['numero_cheque'] = [
                'required',
                'string',
                'max:255',
                Rule::unique('cheques', 'numero')->where(fn ($query) => $query->where('cuenta_bancaria_id', $this->cuenta_bancaria_id)),
            ];
            $rules['banco_cheque'] = ['required', 'string', 'max:255'];
            $rules['fecha_pago_cheque'] = ['required', 'date'];
        }

        $validated = $this->validate($rules);

        try {
            DB::transaction(function () use ($validated) {
                $chequeId = null;

                if ($this->medio_pago === 'cheque') {
                    $cheque = Cheque::emitir(CuentaBancaria::findOrFail($validated['cuenta_bancaria_id']), [
                        'numero' => $validated['numero_cheque'],
                        'banco' => $validated['banco_cheque'],
                        'beneficiario' => $this->transporteSeleccionado,
                        'fecha_pago' => $validated['fecha_pago_cheque'],
                        'monto' => $validated['monto'],
                    ]);

                    $chequeId = $cheque->id;
                }

                OrdenPago::create([
                    'transporte_id' => $validated['transporte_id'],
                    'fecha' => now()->toDateString(),
                    'monto' => $validated['monto'],
                    'concepto' => $validated['concepto'] ?: null,
                    'medio_pago' => $validated['medio_pago'],
                    'cheque_id' => $chequeId,
                ]);
            });
        } catch (\RuntimeException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        $this->reset(['transporte_id', 'transporteSeleccionado', 'monto', 'concepto', 'cuenta_bancaria_id', 'numero_cheque', 'banco_cheque', 'fecha_pago_cheque']);
        $this->medio_pago = 'efectivo';

        unset($this->ordenesPago);
        Flux::modal('orden-pago-form')->close();

        Flux::toast(variant: 'success', text: __('Orden de pago emitida.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Órdenes de pago') }}</flux:heading>
            <flux:subheading>{{ __('Pagos a los transportes por el flete prestado.') }}</flux:subheading>
        </div>

        <flux:modal.trigger name="orden-pago-form">
            <flux:button variant="primary" icon="plus">
                {{ __('Nueva orden de pago') }}
            </flux:button>
        </flux:modal.trigger>
    </div>

    <div class="hidden overflow-x-auto md:block">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Folio') }}</flux:table.column>
                <flux:table.column>{{ __('Fecha') }}</flux:table.column>
                <flux:table.column>{{ __('Transporte') }}</flux:table.column>
                <flux:table.column>{{ __('Concepto') }}</flux:table.column>
                <flux:table.column>{{ __('Medio de pago') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Monto') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->ordenesPago as $orden)
                    <flux:table.row wire:key="orden-{{ $orden->id }}">
                        <flux:table.cell class="font-mono">{{ $orden->folio() }}</flux:table.cell>
                        <flux:table.cell>{{ $orden->fecha->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell class="font-medium">{{ $orden->transporte->nombre }}</flux:table.cell>
                        <flux:table.cell>{{ $orden->concepto ?: '—' }}</flux:table.cell>
                        <flux:table.cell>
                            {{ ucfirst($orden->medio_pago) }}
                            @if ($orden->cheque)
                                <flux:badge size="sm">{{ __('Cheque :n', ['n' => $orden->cheque->numero]) }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end" class="font-mono">$ {{ number_format((float) $orden->monto, 2, ',', '.') }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button size="sm" variant="ghost" icon="printer" :href="route('ordenes-pago.pdf', $orden)" target="_blank" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center text-zinc-500">
                            {{ __('Todavía no hay órdenes de pago.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div class="grid gap-3 md:hidden">
        @forelse ($this->ordenesPago as $orden)
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-white/10" wire:key="orden-mobile-{{ $orden->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-mono text-sm text-zinc-500">{{ $orden->folio() }}</div>
                        <div class="font-medium">{{ $orden->transporte->nombre }}</div>
                    </div>
                    <flux:button size="sm" variant="ghost" icon="printer" :href="route('ordenes-pago.pdf', $orden)" target="_blank" />
                </div>
                <div class="mt-2 text-sm text-zinc-500">
                    {{ $orden->fecha->format('d/m/Y') }} · {{ ucfirst($orden->medio_pago) }}
                    @if ($orden->cheque)
                        <flux:badge size="sm">{{ __('Cheque :n', ['n' => $orden->cheque->numero]) }}</flux:badge>
                    @endif
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-white/5">
                    <span class="text-sm text-zinc-500">{{ $orden->concepto ?: '—' }}</span>
                    <span class="font-mono">$ {{ number_format((float) $orden->monto, 2, ',', '.') }}</span>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-zinc-200 p-4 text-center text-zinc-500 dark:border-white/10">
                {{ __('Todavía no hay órdenes de pago.') }}
            </div>
        @endforelse
    </div>

    {{ $this->ordenesPago->links() }}

    <flux:modal name="orden-pago-form" class="max-w-xl" @close="$set('transporte_id', null); $set('transporteSeleccionado', '')">
        <form wire:submit="emitir" class="space-y-6">
            <flux:heading size="lg">{{ __('Nueva orden de pago') }}</flux:heading>

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

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input wire:model="monto" :label="__('Monto')" type="number" step="0.01" min="0.01" required />
                <flux:input wire:model="concepto" :label="__('Concepto')" placeholder="{{ __('Opcional') }}" />
            </div>

            <flux:select wire:model.live="medio_pago" :label="__('Medio de pago')">
                <flux:select.option value="efectivo">{{ __('Efectivo') }}</flux:select.option>
                <flux:select.option value="transferencia">{{ __('Transferencia') }}</flux:select.option>
                <flux:select.option value="cheque">{{ __('Cheque') }}</flux:select.option>
            </flux:select>

            @if ($medio_pago === 'cheque')
                <div class="space-y-4 rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                    <flux:select wire:model="cuenta_bancaria_id" :label="__('Cuenta bancaria')" placeholder="{{ __('Elegir cuenta...') }}">
                        @foreach ($this->cuentasBancarias as $cuenta)
                            <flux:select.option value="{{ $cuenta->id }}">{{ $cuenta->banco }} ({{ $cuenta->numero_cuenta }})</flux:select.option>
                        @endforeach
                    </flux:select>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <flux:input wire:model="numero_cheque" :label="__('Número de cheque')" required />
                        <flux:input wire:model="banco_cheque" :label="__('Banco del cheque')" required />
                    </div>

                    <flux:input wire:model="fecha_pago_cheque" :label="__('Fecha de pago')" type="date" required />
                </div>
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="emitir">{{ __('Emitir') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
