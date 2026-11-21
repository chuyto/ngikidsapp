<x-app-layout>
    {{-- Header del Componente --}}
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                <i class="fa-solid fa-children text-xl"></i>
            </div>
            <div>
                <h2 class="page-title leading-tight">{{ __('Crear Hijo') }}</h2>
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">
                    Catálogo · Nuevo Registro
                </p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="card overflow-hidden">
            <div class="p-8 border-b border-slate-100">
                <h3 class="font-heading text-xl text-slate-900">Datos del niño</h3>
                <p class="text-sm font-semibold text-slate-500">Completa la información para registrar al niño.</p>
            </div>
            <div class="p-8">
                @include('hijos._form')
            </div>
        </div>
    </div>
</x-app-layout>
