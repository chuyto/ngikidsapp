<x-app-layout>
    {{-- Header del Componente --}}
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                <i class="fa-solid fa-users text-xl"></i>
            </div>
            <div>
                <h2 class="page-title leading-tight">{{ __('Crear Nueva Red') }}</h2>
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">
                    Configuración · Nueva Clasificación
                </p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="card overflow-hidden">
            <div class="p-8 border-b border-slate-100 flex items-center">
                <div class="p-3 bg-brand-50 rounded-2xl border border-brand-100 text-brand-500">
                    <i class="fa-solid fa-circle-plus text-2xl"></i>
                </div>
                <div class="ml-5">
                    <h3 class="font-heading text-xl text-slate-900">Nueva Categoría de Red</h3>
                    <p class="text-sm font-semibold text-slate-500">Registre un nuevo grupo para organizar a los padres y servicios.</p>
                </div>
            </div>
            @include('redes._form')
        </div>
    </div>
</x-app-layout>