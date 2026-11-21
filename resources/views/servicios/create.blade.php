<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                <i class="fa-solid fa-calendar-plus text-xl"></i>
            </div>
            <div>
                <h2 class="page-title leading-tight">{{ __('Nuevo Servicio') }}</h2>
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">Catálogos · Eventos</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="card overflow-hidden">
            <div class="p-8 border-b border-slate-100">
                <h3 class="font-heading text-xl text-slate-900">Datos del servicio</h3>
                <p class="text-sm font-semibold text-slate-500">Completa la información para registrar el evento.</p>
            </div>
            @include('servicios._form')
        </div>
    </div>
</x-app-layout>