<x-app-layout>
    {{-- Header del Componente --}}
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                    <i class="fa-solid fa-users-rectangle text-xl"></i>
                </div>
                <div>
                    <h2 class="page-title leading-tight">{{ __('Gestión de Redes') }}</h2>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">
                        Configuración · Sectores Ministeriales
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        {{-- Panel Principal --}}
        <div class="card overflow-hidden">

            {{-- Toolbar Superior --}}
            <div class="p-8 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-6">
                <div>
                    <h1 class="font-heading text-2xl text-slate-900">Redes</h1>
                    <p class="mt-1 text-sm text-slate-500 font-semibold">Administra las agrupaciones para la clasificación de los registros.</p>
                </div>
                @can('redes.crear')
                <x-create-modal title="Crear Nueva Red" icon="fa-users">
                    @include('redes._form')
                </x-create-modal>
                @endcan
            </div>

            <x-table-toolbar :action="route('redes.index')" placeholder="Buscar red..." />

            {{-- Tabla Estilizada --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/60 border-y border-slate-100">
                        <tr>
                            <th class="th">Descripción de la Red</th>
                            <th class="th text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($redes as $red)
                            <tr class="hover:bg-brand-50/40 transition-all duration-150 group">
                                <td class="td whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-brand-50 rounded-xl flex items-center justify-center text-brand-500 border border-brand-100">
                                            <i class="fa-solid fa-users"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">
                                                {{ $red->descripcion }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="td whitespace-nowrap text-right text-sm font-medium">
                                    @can('redes.editar')
                                    <a href="{{ route('redes.edit', $red->id) }}" class="btn-ghost px-4 py-2 text-xs">
                                        <i class="fa-solid fa-pen"></i>
                                        EDITAR
                                    </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($redes->isEmpty())
                <div class="p-16 text-center">
                    <div class="inline-flex p-6 rounded-full bg-slate-100 mb-4">
                        <i class="fa-solid fa-users-slash text-4xl text-slate-400"></i>
                    </div>
                    <h3 class="font-heading text-xl text-slate-700">Sin Redes Registradas</h3>
                    <p class="text-slate-400 mt-1">Comienza agregando los sectores ministeriales.</p>
                </div>
            @endif
            @if($redes->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">{{ $redes->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
