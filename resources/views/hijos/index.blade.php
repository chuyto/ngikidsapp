<x-app-layout>
    {{-- Header del Componente --}}
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                    <i class="fa-solid fa-children text-xl"></i>
                </div>
                <div>
                    <h2 class="page-title leading-tight">{{ __('Registro de Hijos') }}</h2>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">
                        Catálogo · Niños Registrados
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="card overflow-hidden">

            {{-- Toolbar Superior --}}
            <div class="p-8 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-6">
                <div>
                    <h1 class="font-heading text-2xl text-slate-900">Lista de Hijos</h1>
                    <p class="mt-1 text-sm text-slate-500 font-semibold">Registros activos del ministerio.</p>
                </div>
                @can('hijos.crear')
                    <a href="{{ route('hijos.create') }}" class="btn-primary w-full sm:w-auto">
                        <i class="fa-solid fa-child-reaching"></i>
                        AGREGAR HIJO
                    </a>
                @endcan
            </div>

            <x-table-toolbar :action="route('hijos.index')" placeholder="Buscar niño o responsable..."
                             :sorts="['nombre' => 'Nombre A–Z', 'edad' => 'Mayor edad', 'recientes' => 'Más recientes']" />

            {{-- Pestañas Activos / Papelera --}}
            <div class="px-8 pt-4 flex items-center gap-2">
                <a href="{{ route('hijos.index', array_filter(['search' => request('search'), 'sort' => request('sort')])) }}"
                   class="px-4 py-2 rounded-full text-xs font-extrabold uppercase tracking-widest transition {{ empty($papelera) ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                    <i class="fa-solid fa-children mr-1"></i> Activos
                </a>
                <a href="{{ route('hijos.index', array_filter(['search' => request('search'), 'sort' => request('sort'), 'papelera' => 1])) }}"
                   class="px-4 py-2 rounded-full text-xs font-extrabold uppercase tracking-widest transition {{ ! empty($papelera) ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                    <i class="fa-solid fa-trash-can mr-1"></i> Papelera
                </a>
            </div>

            {{-- Tabla Estilizada --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/60 border-y border-slate-100">
                        <tr>
                            <th class="th">Nombre</th>
                            <th class="th">Padre / Responsable</th>
                            <th class="th">Fecha de Nacimiento</th>
                            <th class="th text-center">Edad</th>
                            <th class="th text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($hijos as $hijo)
                            <tr class="hover:bg-brand-50/40 transition-all duration-150">
                                <td class="td whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center font-extrabold" aria-hidden="true">
                                            {{ mb_substr($hijo->nombre, 0, 1) }}
                                        </div>
                                        <div class="ml-4 text-sm font-extrabold text-slate-800">{{ $hijo->nombre }}</div>
                                    </div>
                                </td>
                                <td class="td whitespace-nowrap text-sm font-semibold text-slate-600">{{ $hijo->padre->nombre ?? 'N/A' }}</td>
                                <td class="td text-sm font-semibold text-slate-600">
                                    {{ $hijo->fecha_nacimiento ? \Carbon\Carbon::parse($hijo->fecha_nacimiento)->format('d/m/Y') : '—' }}
                                </td>
                                <td class="td text-center">
                                    <span class="badge-primary">{{ $hijo->edad ?? '—' }} años</span>
                                </td>
                                <td class="td whitespace-nowrap text-right">
                                    <div class="inline-flex items-center gap-2">
                                        @if (! empty($papelera))
                                            @can('hijos.editar')
                                            <form action="{{ route('hijos.restore', $hijo->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="btn-icon" title="Restaurar" aria-label="Restaurar hijo">
                                                    <i class="fa-solid fa-trash-arrow-up"></i>
                                                </button>
                                            </form>
                                            @endcan
                                            @can('hijos.eliminar')
                                            <form action="{{ route('hijos.forceDelete', $hijo->id) }}" method="POST" class="inline" data-confirm="¿Eliminar DEFINITIVAMENTE a {{ $hijo->nombre }}? No se puede deshacer.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon text-brand-500" title="Eliminar definitivamente" aria-label="Eliminar definitivamente">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        @else
                                            @can('hijos.editar')
                                            <a href="{{ route('hijos.edit', $hijo->id) }}" class="btn-icon" title="Editar" aria-label="Editar hijo">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            @endcan
                                            @can('hijos.eliminar')
                                            <form action="{{ route('hijos.destroy', $hijo->id) }}" method="POST" class="inline" data-confirm="¿Enviar a la papelera a {{ $hijo->nombre }}? Podrás restaurarlo después.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon text-brand-500" title="Enviar a papelera" aria-label="Enviar a papelera">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($hijos->isEmpty())
                <div class="p-16 text-center">
                    <div class="inline-flex p-6 rounded-full bg-slate-100 mb-4">
                        <i class="fa-solid fa-baby text-4xl text-slate-400"></i>
                    </div>
                    <h3 class="font-heading text-xl text-slate-700">No hay hijos registrados</h3>
                    <p class="text-slate-400 mt-1">Comienza creando el primer registro del ministerio.</p>
                </div>
            @endif
            @if($hijos->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">{{ $hijos->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
