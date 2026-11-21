<x-app-layout>
    {{-- Header del Componente --}}
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                    <i class="fa-solid fa-calendar-days text-xl"></i>
                </div>
                <div>
                    <h2 class="page-title leading-tight">{{ __('Gestión de Servicios') }}</h2>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">
                        Configuración · Catálogo de Eventos
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
                    <h1 class="font-heading text-2xl text-slate-900">Catálogo de Servicios</h1>
                    <p class="mt-1 text-sm text-slate-500 font-semibold">Administra los servicios activos para el registro de asistencia.</p>
                </div>
                @can('servicios.crear')
                <x-create-modal title="Nuevo Servicio" icon="fa-calendar-plus">
                    @include('servicios._form')
                </x-create-modal>
                @endcan
            </div>

            <x-table-toolbar :action="route('servicios.index')" placeholder="Buscar servicio..."
                             :sorts="['recientes' => 'Fecha más reciente', 'antiguos' => 'Fecha más antigua', 'descripcion' => 'Descripción A–Z']" />

            {{-- Tabla Estilizada --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/60 border-y border-slate-100">
                        <tr>
                            <th class="th">Descripción</th>
                            <th class="th">Horario</th>
                            <th class="th">Fecha</th>
                            <th class="th text-center">Estado</th>
                            <th class="th text-right">Reportes</th>
                            <th class="th text-right">Editar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($servicios as $servicio)
                            <tr class="hover:bg-brand-50/40 transition-all duration-150">
                                <td class="td whitespace-nowrap font-extrabold text-slate-800">
                                    {{ $servicio->descripcion_servicio }}
                                    <div class="mt-1 flex flex-wrap gap-1.5">
                                        @foreach($servicio->supervisores as $supervisor)
                                            <span class="text-[10px] font-bold text-slate-400">
                                                <i class="fa-solid fa-circle text-[5px] text-brand-400 mr-1 align-middle"></i>{{ $supervisor->nombre }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="td">
                                    <span class="inline-flex items-center text-slate-600 font-semibold">
                                        <i class="fa-regular fa-clock text-slate-400 mr-2"></i>{{ $servicio->horario_servicio }}
                                    </span>
                                </td>
                                <td class="td text-slate-600 font-semibold">{{ \Carbon\Carbon::parse($servicio->fecha_servicio)->format('d/m/Y') }}</td>
                                <td class="td text-center">
                                    @if($servicio->activo)
                                        <span class="badge-success">
                                            <span class="relative flex h-1.5 w-1.5">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                                            </span>
                                            Activo
                                        </span>
                                    @else
                                        <span class="badge-neutral"><i class="fa-solid fa-circle-minus"></i> Inactivo</span>
                                    @endif
                                </td>
                                <td class="td whitespace-nowrap text-right">
                                    @can('reportes.ver')
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('reportes.index', ['servicio_id' => $servicio->id]) }}"
                                           class="btn-ghost px-4 py-2 text-xs">
                                            <i class="fa-solid fa-eye"></i> Detalle
                                        </a>
                                        <a href="{{ route('reportes.pdf', ['servicio_id' => $servicio->id]) }}"
                                           class="btn-icon" title="Descargar PDF" aria-label="Descargar PDF">
                                            <i class="fa-solid fa-file-pdf text-red-500"></i>
                                        </a>
                                    </div>
                                    @else
                                        <span class="text-slate-300 font-mono" aria-hidden="true">—</span>
                                    @endcan
                                </td>
                                <td class="td whitespace-nowrap text-right">
                                    @can('servicios.editar')
                                    <a href="{{ route('servicios.edit', $servicio->id) }}" class="btn-icon" title="Editar" aria-label="Editar servicio">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($servicios->isEmpty())
                <div class="p-16 text-center">
                    <div class="inline-flex p-6 rounded-full bg-slate-100 mb-4">
                        <i class="fa-solid fa-calendar-xmark text-4xl text-slate-400"></i>
                    </div>
                    <h3 class="font-heading text-xl text-slate-700">No hay servicios registrados</h3>
                    <p class="text-slate-400 mt-1">Comienza creando el primer evento del ministerio.</p>
                </div>
            @endif
            @if($servicios->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">{{ $servicios->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
