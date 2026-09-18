@props([
    'sidebar' => false,
])

@php
    $empresa = \App\Models\Configuracion::actual();
    $nombreMarca = $empresa->empresa_nombre ?: config('app.name', 'Laravel');
    $logoUrl = $empresa->logoUrl();
@endphp

@if($sidebar)
    <flux:sidebar.brand :name="$nombreMarca" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-accent-content text-accent-foreground">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $nombreMarca }}" class="size-full object-cover" />
            @else
                <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
            @endif
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="$nombreMarca" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-accent-content text-accent-foreground">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $nombreMarca }}" class="size-full object-cover" />
            @else
                <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
            @endif
        </x-slot>
    </flux:brand>
@endif
