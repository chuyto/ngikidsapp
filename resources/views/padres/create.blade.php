<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                <i class="fa-solid fa-user-plus text-xl"></i>
            </div>
            <div>
                <h2 class="page-title leading-tight">{{ __('Registrar Padre e Hijos') }}</h2>
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">Operaciones · Nuevo Ingreso</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto">
        @include('padres._form', ['redes' => $redes])
    </div>
</x-app-layout>