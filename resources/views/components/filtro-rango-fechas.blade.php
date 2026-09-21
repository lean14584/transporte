<div class="flex flex-col gap-2">
    <div class="flex flex-wrap items-end gap-4">
        <flux:input wire:model.live="desde" :label="__('Desde')" type="date" class="max-w-xs" />
        <flux:input wire:model.live="hasta" :label="__('Hasta')" type="date" class="max-w-xs" />
        @if ($desde || $hasta)
            <flux:button variant="ghost" icon="x-mark" wire:click="limpiarFiltro">
                {{ __('Limpiar filtro') }}
            </flux:button>
        @endif
    </div>

    @if ($rangoInvertido)
        <flux:text size="sm" class="text-red-600 dark:text-red-400">
            {{ __('El rango de fechas está invertido: "Desde" es posterior a "Hasta".') }}
        </flux:text>
    @endif
</div>
