<?php

use App\Livewire\Concerns\FiltraPorRangoDeFechas;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Auditoría')] class extends Component {
    use WithPagination;
    use FiltraPorRangoDeFechas;

    public string $modelo = '';
    public string $usuario_id = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->isAdministrador(), 403);
    }

    public function updatingModelo(): void
    {
        $this->resetPage();
    }

    public function updatingUsuarioId(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function usuarios()
    {
        return User::orderBy('name')->get();
    }

    #[Computed]
    public function logs()
    {
        return AuditLog::query()
            ->with(['user', 'auditable'])
            ->when($this->modelo, fn ($query) => $query->where('auditable_type', $this->modelo))
            ->when($this->usuario_id, fn ($query) => $query->where('user_id', $this->usuario_id))
            ->when($this->desde, fn ($query) => $query->whereDate('created_at', '>=', $this->desde))
            ->when($this->hasta, fn ($query) => $query->whereDate('created_at', '<=', $this->hasta))
            ->latest('created_at')
            ->latest('id')
            ->paginate(25);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Auditoría') }}</flux:heading>
        <flux:subheading>{{ __('Quién hizo qué en el sistema.') }}</flux:subheading>
    </div>

    <div class="flex flex-wrap items-end gap-4">
        <flux:select wire:model.live="modelo" :label="__('Modelo')" class="max-w-xs">
            <flux:select.option value="">{{ __('Todos') }}</flux:select.option>
            @foreach (\App\Models\AuditLog::tiposAuditados() as $clase => $etiqueta)
                <flux:select.option value="{{ $clase }}">{{ $etiqueta }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="usuario_id" :label="__('Usuario')" class="max-w-xs">
            <flux:select.option value="">{{ __('Todos') }}</flux:select.option>
            @foreach ($this->usuarios as $usuario)
                <flux:select.option value="{{ $usuario->id }}">{{ $usuario->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <x-filtro-rango-fechas :desde="$desde" :hasta="$hasta" :rango-invertido="$this->rangoFechasInvertido()" />

    <div class="hidden overflow-x-auto md:block">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Fecha') }}</flux:table.column>
                <flux:table.column>{{ __('Usuario') }}</flux:table.column>
                <flux:table.column>{{ __('Modelo') }}</flux:table.column>
                <flux:table.column>{{ __('Evento') }}</flux:table.column>
                <flux:table.column>{{ __('Cambios') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->logs as $log)
                    <flux:table.row wire:key="log-{{ $log->id }}">
                        <flux:table.cell class="whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                        <flux:table.cell>{{ $log->user?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ \App\Models\AuditLog::etiquetaModelo($log->auditable_type) }} #{{ $log->auditable_id }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="match ($log->event) { 'created' => 'green', 'deleted' => 'red', default => 'blue' }">
                                {{ match ($log->event) { 'created' => __('Alta'), 'deleted' => __('Baja'), default => __('Edición') } }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="max-w-md">
                            <div class="flex flex-col gap-0.5 text-sm">
                                @foreach ($log->changes as $campo => $valores)
                                    <div><span class="font-medium">{{ $campo }}</span>: {{ $valores['old'] ?? '—' }} → {{ $valores['new'] ?? '—' }}</div>
                                @endforeach
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-zinc-500">
                            {{ __('Todavía no hay movimientos registrados.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div class="grid gap-3 md:hidden">
        @forelse ($this->logs as $log)
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-white/10" wire:key="log-mobile-{{ $log->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm text-zinc-500">{{ $log->created_at->format('d/m/Y H:i') }}</div>
                        <div class="font-medium">{{ \App\Models\AuditLog::etiquetaModelo($log->auditable_type) }} #{{ $log->auditable_id }}</div>
                        <div class="text-sm text-zinc-500">{{ $log->user?->name ?? '—' }}</div>
                    </div>
                    <flux:badge size="sm" :color="match ($log->event) { 'created' => 'green', 'deleted' => 'red', default => 'blue' }">
                        {{ match ($log->event) { 'created' => __('Alta'), 'deleted' => __('Baja'), default => __('Edición') } }}
                    </flux:badge>
                </div>
                <div class="mt-3 flex flex-col gap-0.5 border-t border-zinc-100 pt-3 text-sm dark:border-white/5">
                    @foreach ($log->changes as $campo => $valores)
                        <div><span class="font-medium">{{ $campo }}</span>: {{ $valores['old'] ?? '—' }} → {{ $valores['new'] ?? '—' }}</div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-zinc-200 p-4 text-center text-zinc-500 dark:border-white/10">
                {{ __('Todavía no hay movimientos registrados.') }}
            </div>
        @endforelse
    </div>

    {{ $this->logs->links() }}
</div>
