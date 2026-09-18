<x-layouts::auth.transporte :title="__('Iniciar sesión')">
    <div class="flex flex-col gap-6">
        <div class="flex w-full flex-col text-center">
            <flux:heading size="xl" level="1" class="text-white">{{ __('Bienvenido de vuelta') }}</flux:heading>
            <flux:subheading class="text-white/60">{{ __('Ingresá tu usuario y contraseña para entrar al sistema') }}</flux:subheading>
        </div>

        <x-auth-session-status class="text-center" :status="session('status')" />

        <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.04] p-6 shadow-2xl shadow-black/40 backdrop-blur">
            <x-passkey-verify :separator="__('O continuá con tu usuario')" />

            <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
                @csrf

                <!-- Usuario -->
                <flux:input
                    name="username"
                    :label="__('Usuario')"
                    :value="old('username')"
                    type="text"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="usuario"
                    icon="user"
                />

                <!-- Contraseña -->
                <div class="relative">
                    <flux:input
                        name="password"
                        :label="__('Contraseña')"
                        type="password"
                        required
                        autocomplete="current-password"
                        :placeholder="__('Contraseña')"
                        icon="lock-closed"
                        viewable
                    />

                    @if (Route::has('password.request'))
                        <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                            {{ __('¿Olvidaste tu contraseña?') }}
                        </flux:link>
                    @endif
                </div>

                <!-- Recordarme -->
                <flux:checkbox name="remember" :label="__('Recordarme')" :checked="old('remember')" />

                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                        {{ __('Iniciar sesión') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::auth.transporte>
