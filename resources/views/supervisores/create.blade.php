<x-app-layout>
    {{-- Header del Componente --}}
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                <i class="fa-solid fa-user-tie text-xl"></i>
            </div>
            <div>
                <h2 class="page-title leading-tight">{{ __('Registrar Nuevo Supervisor') }}</h2>
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">
                    Configuración · Responsables del Ministerio
                </p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="card overflow-hidden">
            <div class="p-8 border-b border-slate-100 flex items-center">
                <div class="p-3 bg-brand-50 rounded-2xl border border-brand-100 text-brand-500">
                    <i class="fa-solid fa-user-plus text-2xl"></i>
                </div>
                <div class="ml-5">
                    <h3 class="font-heading text-xl text-slate-900">Nuevo Supervisor</h3>
                    <p class="text-sm font-semibold text-slate-500">Registre el responsable del servicio.</p>
                </div>
            </div>
            @include('supervisores._form')
        </div>
    </div>
</x-app-layout>