<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                    <i class="fa-solid fa-user-tie text-xl"></i>
                </div>
                <div>
                    <h2 class="page-title leading-tight">{{ __('Supervisores') }}</h2>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">
                        Administra los supervisores del ministerio.
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        {{-- Tabla de Lista --}}
        <div class="card overflow-hidden">
            <div class="p-8 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-5">
                <div>
                    <h1 class="font-heading text-2xl text-slate-900">Lista de Supervisores</h1>
                    <p class="mt-1 text-sm text-slate-500 font-semibold">Responsables de los servicios del ministerio.</p>
                </div>
                @can('supervisores.crear')
                    <x-create-modal title="Registrar Nuevo Supervisor" icon="fa-user-tie" trigger="AGREGAR SUPERVISOR">
                        @include('supervisores._form')
                    </x-create-modal>
                @endcan
            </div>

            <div class="px-8 py-4 border-b border-slate-100 flex flex-col sm:flex-row gap-3 sm:items-center">
                <form method="GET" action="{{ route('supervisores.index') }}" class="flex flex-col sm:flex-row gap-3 flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" class="input flex-1"
                           placeholder="Buscar supervisor...">
                    <select name="estado" class="input sm:w-44">
                        <option value="">Todos los estados</option>
                        <option value="activos" @selected(request('estado') === 'activos')>Solo activos</option>
                        <option value="inactivos" @selected(request('estado') === 'inactivos')>Solo inactivos</option>
                    </select>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-magnifying-glass"></i> Filtrar
                    </button>
                    @if(request()->filled('search') || request()->filled('estado'))
                        <a href="{{ route('supervisores.index') }}" class="btn-secondary">Limpiar</a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/60 border-y border-slate-100">
                        <tr>
                            <th class="th">Nombre del Supervisor</th>
                            <th class="th">Estado</th>
                            <th class="th text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($supervisores as $sup)
                        <tr class="hover:bg-brand-50/40 transition-all duration-150">
                            <td class="td whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full {{ $sup->activo ? 'bg-brand-100 text-brand-600' : 'bg-slate-100 text-slate-400' }} flex items-center justify-center font-extrabold" aria-hidden="true">
                                        {{ mb_substr($sup->nombre, 0, 1) }}
                                    </div>
                                    <div class="ml-4 text-sm font-extrabold text-slate-800">{{ $sup->nombre }}</div>
                                </div>
                            </td>
                            <td class="td">
                                @if($sup->activo)
                                    <span class="badge-success"><span class="relative flex h-1.5 w-1.5">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                                    </span>Activo</span>
                                @else
                                    <span class="badge-neutral"><i class="fa-solid fa-circle-pause"></i> Inactivo</span>
                                @endif
                            </td>
                            <td class="td text-right">
                                <div class="inline-flex items-center gap-2">
                                    @can('supervisores.editar')
                                        <x-create-modal title="Editar Supervisor" icon="fa-pen"
                                                        trigger="EDITAR" buttonClass="btn-ghost px-4 py-2 text-xs">
                                            @include('supervisores._form', ['supervisor' => $sup])
                                        </x-create-modal>

                                        <form action="{{ route('supervisores.toggle', $sup) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="btn-ghost px-4 py-2 text-xs {{ $sup->activo ? '!text-amber-600' : '!text-emerald-600' }}"
                                                    title="{{ $sup->activo ? 'Desactivar supervisor' : 'Reactivar supervisor' }}">
                                                <i class="fa-solid {{ $sup->activo ? 'fa-circle-pause' : 'fa-circle-play' }}"></i>
                                                {{ $sup->activo ? 'DESACTIVAR' : 'REACTIVAR' }}
                                            </button>
                                        </form>
                                    @endcan
                                    @can('supervisores.eliminar')
                                        <form action="{{ route('supervisores.destroy', $sup) }}" method="POST" class="inline"
                                              data-confirm="¿Eliminar al supervisor {{ $sup->nombre }}? Si solo dejará de participar, es mejor desactivarlo.">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-danger px-4 py-2 text-xs">
                                                <i class="fa-solid fa-trash-can"></i>
                                                Eliminar
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="td p-16 text-center">
                                <div class="inline-flex p-6 rounded-full bg-slate-100 mb-4">
                                    <i class="fa-solid fa-user-slash text-4xl text-slate-400"></i>
                                </div>
                                <h3 class="font-heading text-xl text-slate-700">No hay supervisores registrados</h3>
                                <p class="text-slate-400 mt-1">Usa el botón “Agregar supervisor” para crear el primer registro.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($supervisores->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">{{ $supervisores->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
