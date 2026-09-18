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
            @keyframes transporte-pin-pulse {
                0% { r: 5; opacity: .9; }
                70% { r: 20; opacity: 0; }
                100% { r: 20; opacity: 0; }
            }
            .transporte-blob { animation: transporte-blob-drift 14s ease-in-out infinite; }
            .transporte-blob-delay { animation-delay: -6s; }
            .transporte-float { animation: transporte-float 5s ease-in-out infinite; }
            .transporte-float-delay { animation: transporte-float 5s ease-in-out infinite; animation-delay: -2.5s; }
            .transporte-dot-grid {
                background-image: radial-gradient(rgba(255,255,255,.16) 1px, transparent 1px);
                background-size: 22px 22px;
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

                <div class="relative z-10 flex flex-1 items-center justify-center py-10">
                    <svg viewBox="0 0 400 320" class="w-full max-w-sm" role="img" aria-hidden="true">
                        <path d="M20,270 C90,230 70,170 140,150 C210,130 220,80 300,55"
                              fill="none" stroke="rgba(255,255,255,.35)" stroke-width="3"
                              stroke-linecap="round" stroke-dasharray="2 14" />

                        <circle cx="20" cy="270" r="5" fill="rgba(103,232,249,.9)" />

                        <g>
                            <circle cx="300" cy="55" r="5" fill="#22d3ee" />
                            <circle cx="300" cy="55" r="5" fill="#22d3ee" opacity="0">
                                <animate attributeName="r" values="5;22;22" dur="2.4s" repeatCount="indefinite" />
                                <animate attributeName="opacity" values=".8;0;0" dur="2.4s" repeatCount="indefinite" />
                            </circle>
                        </g>

                        <g>
                            <g transform="translate(-24,-14)">
                                <rect x="0" y="14" width="34" height="16" rx="3" fill="#e2e8f0" />
                                <path d="M34 18h13l8 8v4H34z" fill="#67e8f9" />
                                <rect x="42" y="20" width="8" height="6" fill="#0e7490" opacity=".6" />
                                <circle cx="12" cy="32" r="5" fill="#0f172a" />
                                <circle cx="12" cy="32" r="2" fill="#94a3b8" />
                                <circle cx="45" cy="32" r="5" fill="#0f172a" />
                                <circle cx="45" cy="32" r="2" fill="#94a3b8" />
                                <animateMotion dur="9s" repeatCount="indefinite" rotate="auto"
                                    path="M20,270 C90,230 70,170 140,150 C210,130 220,80 300,55" />
                            </g>
                        </g>

                        <g class="transporte-float" opacity=".85">
                            <rect x="330" y="150" width="26" height="26" rx="5" fill="rgba(255,255,255,.12)" stroke="rgba(255,255,255,.35)" />
                        </g>
                        <g class="transporte-float-delay" opacity=".85">
                            <rect x="40" y="70" width="20" height="20" rx="5" fill="rgba(255,255,255,.12)" stroke="rgba(255,255,255,.35)" />
                        </g>
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
