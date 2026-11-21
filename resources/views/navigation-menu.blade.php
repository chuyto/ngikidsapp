<div class="flex flex-col flex-1 min-h-0">
    <nav class="px-4 py-4 space-y-1 flex-grow overflow-y-auto no-scrollbar">
        <p class="px-2 mb-3 text-[10px] font-extrabold uppercase tracking-[0.2em] text-slate-400">
            Menú Principal
        </p>

        {{-- Registro --}}
        @can('padres.ver')
        <a href="{{ route('padres.index') }}"
           class="nav-item {{ request()->routeIs('padres.*') ? 'nav-item-active' : '' }}">
            <i class="fa-solid fa-address-book w-5 text-center text-base"></i>
            <span>Registro</span>
        </a>
        @endcan

        {{-- Asistencia --}}
        @can('asistencias.crear')
        <a href="{{ route('asistencias.create') }}"
           class="nav-item {{ request()->routeIs('asistencias.create') ? 'nav-item-active' : '' }}">
            <i class="fa-solid fa-clipboard-check w-5 text-center text-base"></i>
            <span>Asistencia</span>
        </a>
        @endcan

        {{-- Reportes --}}
        @can('reportes.ver')
        <a href="{{ route('reportes.index') }}"
           class="nav-item {{ request()->routeIs('reportes.*') ? 'nav-item-active' : '' }}">
            <i class="fa-solid fa-chart-column w-5 text-center text-base"></i>
            <span>Reportes</span>
        </a>
        @endcan

        {{-- Catálogos --}}
        @if(auth()->user()->can('servicios.ver') || auth()->user()->can('redes.ver') || auth()->user()->can('supervisores.ver'))
        <div x-data="{ open: {{ request()->routeIs('servicios.*', 'redes.*', 'supervisores.*') ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="nav-item w-full justify-between">
                <span class="flex items-center gap-3">
                    <i class="fa-solid fa-book-open w-5 text-center text-base"></i>
                    <span>Catálogos</span>
                </span>
                <i :class="open ? 'fa-chevron-up' : 'fa-chevron-down'" class="fa-solid text-xs text-slate-400 transition-transform"></i>
            </button>
            <div x-show="open" x-transition class="ml-9 mt-1 space-y-1 border-l-2 border-brand-100 pl-4">
                @can('servicios.ver')
                <a href="{{ route('servicios.index') }}"
                   class="block rounded-xl px-3 py-2 text-sm font-semibold transition-colors duration-200 cursor-pointer {{ request()->routeIs('servicios.*') ? 'text-brand-600 bg-brand-50' : 'text-slate-500 hover:text-brand-600 hover:bg-slate-50' }}">
                    <i class="fa-solid fa-calendar-days mr-2"></i>Servicios
                </a>
                @endcan
                @can('redes.ver')
                <a href="{{ route('redes.index') }}"
                   class="block rounded-xl px-3 py-2 text-sm font-semibold transition-colors duration-200 cursor-pointer {{ request()->routeIs('redes.*') ? 'text-brand-600 bg-brand-50' : 'text-slate-500 hover:text-brand-600 hover:bg-slate-50' }}">
                    <i class="fa-solid fa-circle-nodes mr-2"></i>Redes
                </a>
                @endcan
                @can('supervisores.ver')
                <a href="{{ route('supervisores.index') }}"
                   class="block rounded-xl px-3 py-2 text-sm font-semibold transition-colors duration-200 cursor-pointer {{ request()->routeIs('supervisores.*') ? 'text-brand-600 bg-brand-50' : 'text-slate-500 hover:text-brand-600 hover:bg-slate-50' }}">
                    <i class="fa-solid fa-user-shield mr-2"></i>Supervisores
                </a>
                @endcan
            </div>
        </div>
        @endif

        {{-- Usuarios --}}
        @can('usuarios.ver')
        <a href="{{ route('usuarios.index') }}"
           class="nav-item {{ request()->routeIs('usuarios.*') ? 'nav-item-active' : '' }}">
            <i class="fa-solid fa-user-gear w-5 text-center text-base"></i>
            <span>Usuarios</span>
        </a>
        @endcan

        {{-- Auditoría --}}
        @can('auditoria.ver')
        <a href="{{ route('auditoria.index') }}"
           class="nav-item {{ request()->routeIs('auditoria.*') ? 'nav-item-active' : '' }}">
            <i class="fa-solid fa-clipboard-list w-5 text-center text-base"></i>
            <span>Auditoría</span>
        </a>
        @endcan

        {{-- Ayuda --}}
        <a href="{{ route('ayuda') }}"
           class="nav-item {{ request()->routeIs('ayuda') ? 'nav-item-active' : '' }}">
            <i class="fa-solid fa-circle-question w-5 text-center text-base"></i>
            <span>Ayuda</span>
        </a>
    </nav>

    {{-- Footer fijado al fondo del sidebar --}}
    <footer class="px-4 pb-5 pt-4 border-t border-white/60 bg-white/40 backdrop-blur-xl flex-shrink-0">
        @if($servicioActivo)
            <div class="mb-4 flex items-center gap-3 rounded-2xl bg-brand-50 border border-brand-100 px-4 py-3">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                </span>
                <div class="min-w-0">
                    <p class="text-[9px] font-extrabold uppercase text-brand-400 tracking-widest">Servicio Activo</p>
                    <p class="text-xs font-bold text-slate-700 truncate">{{ $servicioActivo->descripcion_servicio }}</p>
                </div>
            </div>
        @endif

        <div x-data="{ userMenu: false }" class="relative">
            <button @click="userMenu = !userMenu" class="flex items-center w-full gap-3 p-2 rounded-2xl hover:bg-slate-50 transition-colors cursor-pointer">
                <div class="h-10 w-10 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center text-sm font-extrabold">
                    {{ mb_substr(Auth::user()->name, 0, 1) }}
                </div>
                <div class="flex-grow text-left">
                    <p class="text-sm font-bold text-slate-800 truncate">{{ Auth::user()->name }}</p>
                    <p class="text-[10px] font-semibold text-slate-400">{{ Auth::user()->getRoleNames()->first() ?? 'Sin rol' }}</p>
                </div>
                <i class="fa-solid fa-ellipsis-vertical text-slate-400 text-sm"></i>
            </button>

            <div x-show="userMenu" @click.away="userMenu = false" x-transition
                 class="absolute bottom-full left-0 w-full mb-3 bg-white border border-slate-200 rounded-2xl shadow-xl overflow-hidden py-2 z-40">
                <a href="{{ route('profile.show') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer">
                    <i class="fa-solid fa-user text-slate-400 w-4 text-center"></i> Mi Perfil
                </a>
                <hr class="my-1 border-slate-100">
                <form method="POST" action="{{ route('logout') }}"
                      onsubmit="navigator.serviceWorker?.controller?.postMessage({ type: 'CLEAR_CACHES' })">
                    @csrf
                    <button type="submit" class="flex items-center w-full gap-3 px-4 py-2.5 text-sm font-bold text-brand-600 hover:bg-brand-50 cursor-pointer">
                        <i class="fa-solid fa-arrow-right-from-bracket w-4 text-center"></i> Cerrar Sesión
                    </button>
                </form>
            </div>
        </div>
    </footer>
</div>