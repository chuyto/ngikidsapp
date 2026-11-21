<x-app-layout>
    {{-- Header del Componente --}}
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                    <i class="fa-solid fa-circle-question text-xl"></i>
                </div>
                <div>
                    <h2 class="page-title leading-tight">{{ __('Ayuda y Soporte') }}</h2>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">
                        Información del desarrollador
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6">

        {{-- Header Decorativo --}}
        <div class="card overflow-hidden">
            <div class="relative h-32 bg-gradient-to-r from-brand-400 to-pink-500">
                <div class="absolute -bottom-8 left-8">
                    <div class="h-16 w-16 rounded-2xl bg-white shadow-xl border-4 border-brand-50 flex items-center justify-center text-3xl text-brand-500">
                        <i class="fa-solid fa-circle-question"></i>
                    </div>
                </div>
            </div>

            <div class="pt-16 p-8">
                {{-- Título y Descripción --}}
                <div class="mb-8">
                    <h1 class="font-heading text-3xl text-slate-900">Ayuda y Soporte Técnico</h1>
                    <p class="text-slate-500 font-semibold mt-2">Información del desarrollador y canales de contacto oficial.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Columna Izquierda: Desarrollador --}}
                    <div class="space-y-4">
                        <h2 class="text-sm font-black text-brand-600 uppercase tracking-widest">Desarrollado por</h2>
                        <div class="card p-6">
                            <p class="text-xl font-heading font-bold text-slate-800">Jesus Arturo Sanchez Garcia</p>
                            <p class="text-sm font-semibold text-slate-500 mb-4">Full Stack Developer</p>

                            <a href="https://jsanchez.is-a.dev/"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="inline-flex items-center gap-2 text-brand-600 hover:text-brand-800 transition-colors text-sm font-bold">
                                <i class="fa-solid fa-globe"></i>
                                jsanchez.is-a.dev
                            </a>
                        </div>
                    </div>

                    {{-- Columna Derecha: Contacto --}}
                    <div class="space-y-4">
                        <h2 class="text-sm font-black text-emerald-600 uppercase tracking-widest">¿Necesitas Ayuda?</h2>
                        <a href="https://wa.me/529931631309?text=Hola%20Arturo,%20necesito%20soporte%20con%20la%20App%20NGIKids"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="block group card p-6 hover:border-emerald-200 transition-all">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-lg font-heading font-bold text-emerald-600">WhatsApp Soporte</p>
                                    <p class="text-sm font-semibold text-slate-500 mt-1">993 163 1309</p>
                                </div>
                                <div class="h-12 w-12 bg-emerald-500 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/20 group-hover:scale-110 transition-transform">
                                    <i class="fa-brands fa-whatsapp text-2xl text-white"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                {{-- Footer / Copyright --}}
                <div class="mt-12 pt-8 border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="text-center md:text-left">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">Copyright © {{ date('Y') }} NGIKids</p>
                        <p class="text-xs text-slate-400 mt-1">Todos los derechos reservados.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span class="text-xs font-semibold text-slate-500">NGIKids App v1.0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>