<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <style>
            @keyframes transporte-blob-drift {
                0%, 100% { transform: translate(0, 0) scale(1); }
                50% { transform: translate(18px, -24px) scale(1.06); }
            }
            @keyframes transporte-float {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-10px); }
            }
            @keyframes transporte-truck-bounce {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-10px); }
            }
            .transporte-blob { animation: transporte-blob-drift 14s ease-in-out infinite; }
            .transporte-blob-delay { animation-delay: -6s; }
            .transporte-float { animation: transporte-float 5s ease-in-out infinite; }
            .transporte-float-delay { animation: transporte-float 5s ease-in-out infinite; animation-delay: -2.5s; }
            .transporte-truck-bounce { animation: transporte-truck-bounce 3.2s ease-in-out infinite; }
            .transporte-dot-grid {
                background-image: radial-gradient(rgba(255,255,255,.16) 1px, transparent 1px);
                background-size: 22px 22px;
            }
            /* Los labels de Flux usan un selector :where() de especificidad
               cero para el color oscuro, pensado para cards claras. Acá el
               fondo del formulario es casi negro, así que se fuerza texto
               claro sin depender de que el dark: de Tailwind gane esa pulseada. */
            .transporte-login-form [data-flux-label],
            .transporte-login-form [data-flux-description] {
                color: #f4f4f5;
            }
        </style>
    </head>
    <body class="min-h-screen bg-zinc-950 antialiased">
        <div class="relative grid min-h-dvh lg:grid-cols-2">
            {{-- Panel de marca --}}
            <div class="relative hidden overflow-hidden bg-gradient-to-br from-blue-950 via-indigo-900 to-cyan-900 lg:flex lg:flex-col lg:justify-between lg:p-12">
                <div class="transporte-dot-grid absolute inset-0 opacity-40"></div>
                <div class="transporte-blob absolute -left-24 top-10 size-72 rounded-full bg-cyan-500/30 blur-3xl"></div>
                <div class="transporte-blob transporte-blob-delay absolute -right-16 bottom-24 size-80 rounded-full bg-indigo-500/30 blur-3xl"></div>

                @php
                    $empresa = \App\Models\Configuracion::actual();
                    $nombreMarca = $empresa->empresa_nombre ?: config('app.name', 'Transporte');
                    $logoUrl = $empresa->logoUrl();
                @endphp

                <a href="{{ route('home') }}" class="relative z-10 flex items-center gap-3 text-lg font-semibold text-white" wire:navigate>
                    <span class="flex size-11 items-center justify-center overflow-hidden rounded-xl bg-white/10 ring-1 ring-white/20 backdrop-blur">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $nombreMarca }}" class="size-full object-cover" />
                        @else
                            <svg viewBox="0 0 24 24" fill="none" class="size-6 text-cyan-300">
                                <path d="M2 16.5V6.75A1.75 1.75 0 0 1 3.75 5h9.5A1.75 1.75 0 0 1 15 6.75v9.75M2 16.5h13M2 16.5a2 2 0 1 0 4 0 2 2 0 0 0-4 0Zm13 0a2 2 0 1 0 4 0 2 2 0 0 0-4 0Zm0 0h4.25c.69 0 1.25-.56 1.25-1.25v-3.22a1.75 1.75 0 0 0-.4-1.11l-2.1-2.58a1.75 1.75 0 0 0-1.36-.65H15v8.81Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        @endif
                    </span>
                    {{ $nombreMarca }}
                </a>

                <div class="relative z-10 flex flex-1 flex-col items-center justify-center gap-8 py-6">
                    <div class="relative">
                        <div class="absolute inset-0 -z-10 scale-125 rounded-full bg-cyan-400/25 blur-3xl"></div>

                        <div class="transporte-float absolute -left-10 -top-4 rounded-lg border border-white/25 bg-white/10 p-2 backdrop-blur">
                            <svg viewBox="0 0 24 24" fill="none" class="size-6 text-cyan-200"><path stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5M3.75 15h16.5M6.75 4.5v15m10.5-15v15" /></svg>
                        </div>
                        <div class="transporte-float-delay absolute -right-8 bottom-6 rounded-lg border border-white/25 bg-white/10 p-2 backdrop-blur">
                            <svg viewBox="0 0 24 24" fill="none" class="size-5 text-cyan-200"><path stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" d="M12 2 3 6v6c0 5 4 9 9 10 5-1 9-5 9-10V6l-9-4Z" /></svg>
                        </div>

                        <svg viewBox="0 0 200 120" class="transporte-truck-bounce h-52 w-80 drop-shadow-2xl" role="img" aria-hidden="true">
                            <rect x="8" y="34" width="110" height="52" rx="7" fill="#f8fafc" />
                            <rect x="8" y="34" width="110" height="13" rx="6" fill="#22d3ee" />
                            <rect x="8" y="64" width="110" height="4" fill="#cbd5e1" />

                            <rect x="118" y="50" width="72" height="36" rx="3" fill="#0891b2" />
                            <rect x="134" y="58" width="42" height="20" rx="2" fill="#083344" />
                            <rect x="137" y="61" width="36" height="13" rx="2" fill="#a5f3fc" />
                            <circle cx="184" cy="83" r="3.5" fill="#fef08a" />

                            <circle cx="44" cy="90" r="15" fill="#0f172a" />
                            <circle cx="44" cy="90" r="6.5" fill="#94a3b8" />
                            <circle cx="152" cy="90" r="15" fill="#0f172a" />
                            <circle cx="152" cy="90" r="6.5" fill="#94a3b8" />
                        </svg>
                    </div>

                    <svg viewBox="0 0 320 16" class="w-72 max-w-full opacity-70" aria-hidden="true">
                        <line x1="0" y1="8" x2="320" y2="8" stroke="rgba(255,255,255,.4)" stroke-width="4" stroke-linecap="round" stroke-dasharray="4 20" />
                    </svg>
                </div>

                <div class="relative z-10 space-y-4">
                    <blockquote class="max-w-sm text-xl font-medium leading-snug text-white">
                        {{ __('Cada guía, cada kilo y cada cobro, siempre a la vista.') }}
                    </blockquote>
                    @if ($empresa->empresa_direccion)
                        <p class="text-sm text-white/60">{{ $empresa->empresa_direccion }}</p>
                    @endif
                    <div class="flex flex-wrap gap-2 pt-2">
                        @foreach ([__('Guías de carga'), __('Cuenta corriente'), __('Cobranzas'), __('Órdenes de pago')] as $chip)
                            <span class="rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-medium text-white/90 backdrop-blur">
                                {{ $chip }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Panel de acceso --}}
            <div class="flex items-center justify-center bg-zinc-950 p-6 lg:bg-gradient-to-b lg:from-zinc-900 lg:to-zinc-950">
                <div class="mx-auto flex w-full max-w-sm flex-col justify-center gap-6">
                    <a href="{{ route('home') }}" class="z-20 flex flex-col items-center gap-2 font-medium lg:hidden" wire:navigate>
                        <span class="flex h-9 w-9 items-center justify-center rounded-md">
                            <x-app-logo-icon class="size-9 fill-current text-white" />
                        </span>
                        <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                    </a>

                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
