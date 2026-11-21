<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NGI Kids | Acceso interno</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.bunny.net/css?family=nunito-sans:400,600,700,800&family=varela-round:400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
        body { font-family: 'Nunito Sans', sans-serif; }
        h1, h2, h3 { font-family: 'Varela Round', 'Nunito Sans', sans-serif; }
        .animate-fade-in { animation: fadeIn .9s ease-out; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .blob {
            position: absolute;
            border-radius: 9999px;
            filter: blur(70px);
            opacity: .5;
            pointer-events: none;
        }
        @media (prefers-reduced-motion: reduce) {
            * { animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body class="bg-cream text-slate-800 overflow-x-hidden">

    <div class="relative min-h-screen flex flex-col">

        {{-- Fondo decorativo sutil --}}
        <div class="blob w-96 h-96 bg-brand-200 -top-20 -left-20"></div>
        <div class="blob w-96 h-96 bg-amber-100 top-1/3 -right-24"></div>

        <header class="relative z-10 max-w-6xl w-full mx-auto px-6 py-8 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <img class="h-12 w-auto" src="{{ asset('img/logo-ngikids.png') }}" alt="NGI Kids Logo">
                <div>
                    <span class="font-bold text-2xl text-slate-900 leading-none">NGIKids</span>
                    <p class="text-[10px] font-bold uppercase tracking-[0.3em] text-slate-500">Sistema interno</p>
                </div>
            </div>
            @auth
                <a href="{{ route('dashboard') }}" class="btn-primary">
                    <i class="fa-solid fa-table-columns"></i> Panel
                </a>
            @endauth
        </header>

        <main class="relative z-10 max-w-4xl w-full mx-auto px-6 pb-20 flex-1 animate-fade-in">

            @guest
                {{-- Portal de acceso: login integrado --}}
                <div class="max-w-md mx-auto pt-6 lg:pt-10">
                    <div class="text-center">
                        <span class="inline-flex items-center gap-2 rounded-full glass px-4 py-1.5 text-xs font-bold text-slate-600 shadow-sm">
                            <i class="fa-solid fa-church text-brand-500"></i> Ministerio Infantil · Uso interno
                        </span>
                        <h1 class="mt-6 text-3xl lg:text-4xl font-bold text-slate-900 leading-tight">
                            Acceso del equipo
                        </h1>
                        <p class="mt-3 text-slate-500">
                            {{ \Carbon\Carbon::now()->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}
                        </p>
                    </div>

                    <div class="card p-8 mt-8">
                        <x-validation-errors class="mb-5" />

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div>
                                <x-label for="email" value="Correo electrónico" />
                                <div class="relative">
                                    <i class="fa-solid fa-envelope text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 text-sm"></i>
                                    <x-input id="email" class="pl-11" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="tucorreo@ejemplo.com" />
                                </div>
                            </div>

                            <div class="mt-5">
                                <x-label for="password" value="Contraseña" />
                                <div class="relative">
                                    <i class="fa-solid fa-lock text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 text-sm"></i>
                                    <x-input id="password" class="pl-11" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
                                </div>
                            </div>

                            <div class="flex items-center justify-between mt-5">
                                <label for="remember_me" class="flex items-center cursor-pointer select-none">
                                    <x-checkbox id="remember_me" name="remember" class="rounded-md border-slate-300 text-brand-600 focus:ring-brand-500" />
                                    <span class="ms-2 text-sm font-semibold text-slate-600">Recordarme</span>
                                </label>

                                @if (Route::has('password.request'))
                                    <a class="link text-sm" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
                                @endif
                            </div>

                            <div class="mt-7">
                                <x-button class="w-full">
                                    <i class="fa-solid fa-right-to-bracket"></i> Iniciar sesión
                                </x-button>
                            </div>
                        </form>
                    </div>

                    <p class="text-center text-xs font-semibold text-slate-400 mt-6">
                        Acceso exclusivo para el equipo NGI Kids. ·
                        <a href="{{ route('privacidad') }}" class="link">Aviso de Privacidad</a>
                    </p>
                </div>
            @else
                {{-- Equipo: saludo + accesos directos por módulo --}}
                <div class="pt-4 lg:pt-8">
                    <p class="text-[11px] font-black uppercase tracking-[0.25em] text-slate-400">
                        {{ \Carbon\Carbon::now()->locale('es')->isoFormat('dddd D [de] MMMM') }}
                    </p>
                    <h1 class="mt-2 text-3xl lg:text-4xl font-bold text-slate-900 leading-tight">
                        Hola, {{ explode(' ', trim(Auth::user()->name))[0] }}
                    </h1>

                    @if ($servicioActivo)
                        <p class="mt-4 inline-flex items-center gap-2 rounded-full bg-emerald-50 border border-emerald-200 px-4 py-1.5 text-xs font-extrabold text-emerald-700 uppercase tracking-widest">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            Servicio activo: {{ $servicioActivo->descripcion_servicio }}
                        </p>
                    @else
                        <p class="mt-4 inline-flex items-center gap-2 rounded-full bg-slate-100 border border-slate-200 px-4 py-1.5 text-xs font-extrabold text-slate-500 uppercase tracking-widest">
                            <i class="fa-solid fa-calendar-xmark"></i> Sin servicio activo
                        </p>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mt-10 text-left">
                        @can('asistencias.crear')
                            <a href="{{ route('asistencias.create') }}" class="card card-hover p-6 flex items-center gap-4">
                                <span class="w-12 h-12 shrink-0 rounded-2xl bg-brand-100 text-brand-600 flex items-center justify-center text-xl">
                                    <i class="fa-solid fa-clipboard-check"></i>
                                </span>
                                <span>
                                    <span class="block font-bold text-slate-900">Registrar asistencia</span>
                                    <span class="block mt-1 text-sm text-slate-500">Entradas y salidas del servicio de hoy.</span>
                                </span>
                            </a>
                        @endcan
                        @can('padres.ver')
                            <a href="{{ route('padres.index') }}" class="card card-hover p-6 flex items-center gap-4">
                                <span class="w-12 h-12 shrink-0 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl">
                                    <i class="fa-solid fa-address-book"></i>
                                </span>
                                <span>
                                    <span class="block font-bold text-slate-900">Directorio de familias</span>
                                    <span class="block mt-1 text-sm text-slate-500">Padres, niños y credenciales.</span>
                                </span>
                            </a>
                        @endcan
                        @can('reportes.ver')
                            <a href="{{ route('reportes.index') }}" class="card card-hover p-6 flex items-center gap-4">
                                <span class="w-12 h-12 shrink-0 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl">
                                    <i class="fa-solid fa-chart-column"></i>
                                </span>
                                <span>
                                    <span class="block font-bold text-slate-900">Reportes</span>
                                    <span class="block mt-1 text-sm text-slate-500">Asistencia por servicio y red.</span>
                                </span>
                            </a>
                        @endcan
                        @can('servicios.ver')
                            <a href="{{ route('servicios.index') }}" class="card card-hover p-6 flex items-center gap-4">
                                <span class="w-12 h-12 shrink-0 rounded-2xl bg-sky-100 text-sky-600 flex items-center justify-center text-xl">
                                    <i class="fa-solid fa-calendar-days"></i>
                                </span>
                                <span>
                                    <span class="block font-bold text-slate-900">Servicios</span>
                                    <span class="block mt-1 text-sm text-slate-500">Activar y programar servicios.</span>
                                </span>
                            </a>
                        @endcan
                    </div>

                    <p class="text-center text-sm italic font-semibold text-slate-400 mt-12">
                        «Instruye al niño en su camino, y aun cuando fuere viejo no se apartará de él.» — Prov. 22:6
                    </p>
                </div>
            @endauth
        </main>

        <footer class="relative z-10 pb-10 text-center text-xs font-semibold text-slate-400">
            &copy; 2026 <a href="https://jsanchez.is-a.dev/" target="_blank" class="link">/JSanchez</a> | NGI Kids App ·
            <a href="{{ route('privacidad') }}" class="link">Aviso de Privacidad</a>
        </footer>
    </div>

</body>
</html>
