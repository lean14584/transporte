<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
        <flux:sidebar sticky collapsible="mobile" class="dark border-e border-ink-line bg-ink">
            <flux:sidebar.header class="border-b border-ink-line">
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Principal')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                @php
                    $enOperacion = request()->routeIs('guias.*', 'transportes.*', 'fleteros.*', 'productos.*', 'clientes.*');
                    $enFinanzas = request()->routeIs('caja.*', 'cobranzas.*', 'ordenes-pago.*', 'cheques.*', 'cuentas-bancarias.*');
                    $enSistema = request()->routeIs('usuarios.*', 'auditoria.*', 'configuracion.*');
                @endphp

                <flux:sidebar.group
                    :heading="__('Operación')"
                    class="grid"
                    expandable
                    :expanded="$enOperacion || (! $enFinanzas && ! $enSistema)"
                >
                    <flux:sidebar.item icon="document-text" :href="route('guias.index')" :current="request()->routeIs('guias.*')" wire:navigate>
                        {{ __('Guías') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="truck" :href="route('transportes.index')" :current="request()->routeIs('transportes.*')" wire:navigate>
                        {{ __('Transportes') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="identification" :href="route('fleteros.index')" :current="request()->routeIs('fleteros.*')" wire:navigate>
                        {{ __('Fleteros') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="cube" :href="route('productos.index')" :current="request()->routeIs('productos.*')" wire:navigate>
                        {{ __('Productos') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="user-group" :href="route('clientes.index')" :current="request()->routeIs('clientes.*')" wire:navigate>
                        {{ __('Clientes') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                @unless (auth()->user()->isUsuarioCarga())
                    <flux:sidebar.group :heading="__('Finanzas')" class="grid" expandable :expanded="$enFinanzas">
                        <flux:sidebar.item icon="calculator" :href="route('caja.index')" :current="request()->routeIs('caja.*')" wire:navigate>
                            {{ __('Caja') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="banknotes" :href="route('cobranzas.index')" :current="request()->routeIs('cobranzas.*')" wire:navigate>
                            {{ __('Cobranzas') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="clipboard-document-check" :href="route('ordenes-pago.index')" :current="request()->routeIs('ordenes-pago.*')" wire:navigate>
                            {{ __('Órdenes de pago') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="rectangle-stack" :href="route('cheques.index')" :current="request()->routeIs('cheques.*')" wire:navigate>
                            {{ __('Cheques') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="building-library" :href="route('cuentas-bancarias.index')" :current="request()->routeIs('cuentas-bancarias.*')" wire:navigate>
                            {{ __('Cuentas bancarias') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>

                    @if (auth()->user()->isAdministrador())
                        <flux:sidebar.group :heading="__('Sistema')" class="grid" expandable :expanded="$enSistema">
                            <flux:sidebar.item icon="users" :href="route('usuarios.index')" :current="request()->routeIs('usuarios.*')" wire:navigate>
                                {{ __('Usuarios') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="clipboard-document-list" :href="route('auditoria.index')" :current="request()->routeIs('auditoria.*')" wire:navigate>
                                {{ __('Auditoría') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="adjustments-horizontal" :href="route('configuracion.edit')" :current="request()->routeIs('configuracion.*')" wire:navigate>
                                {{ __('Configuración') }}
                            </flux:sidebar.item>
                        </flux:sidebar.group>
                    @endif
                @endunless
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden border-t border-ink-line lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="dark border-b border-ink-line bg-ink lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Configuración de cuenta') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Cerrar sesión') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
